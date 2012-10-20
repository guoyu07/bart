<?php
namespace Bart;

class ShellTest extends BaseTestCase
{
	private function createMockShell()
	{
		return new \Bart\Stub\MockShell($this);
	}

	/**
	 * Provide a temporary file path to use for tests and always make sure it gets removed
	 * @param callable $func Will do the stuff to the temporary file
	 */
	protected function doStuffToTempFile($func)
	{
		$filename = BART_DIR . 'phpunit-random-file-please-delete.txt';
		@unlink($filename);
		$shell = new Shell();

		try
		{
			$func($this, $shell, $filename);
		}
		catch (\Exception $e)
		{
			@unlink($filename);
			throw $e;
		}

		@unlink($filename);
	}


	public function test_shell_exec_stubbed()
	{
		// Can we successfully mock the Shell class shell_exec method?
		$shell_stub = $this->getMock('Bart\Shell');
		$shell_stub->expects($this->once())
				->method('shell_exec')
				->with($this->equalTo('whoami'))
				->will($this->returnValue('john braynard'));

		$this->assertEquals('john braynard', $shell_stub->shell_exec('whoami'));
	}

	public function test_shell_exec_real()
	{
		// Non-brittle - this value shouldn't change during test!
		$iam = shell_exec('whoami');
		$shell = new Shell();

		$this->assertEquals($iam, $shell->shell_exec('whoami'));
	}

	public function test_mock_shell___call_method()
	{
		$phpu_mock_shell = $this->getMock('Bart\Shell');
		$phpu_mock_shell->expects($this->once())
				->method('parse_ini_file')
				->with($this->equalTo('/etc/php.ini'), $this->equalTo(false))
				->will($this->returnValue('some parsed junk'));
		$shell = new \Bart\Stub\MockShell($this, $phpu_mock_shell);
		$parsed = $shell->parse_ini_file('/etc/php.ini', false);

		$this->assertEquals('some parsed junk', $parsed);
	}

	// @Note not going to test for real since it echos straight out
	public function test_passthru_mock()
	{
		// Does our Mock_Shell class work?
		$shell = self::createMockShell($this);
		$shell->expectPassthru('whoami', true);

		$success = false;;
		$shell->passthru('whoami', $success);
		$this->assertSame(true, $success, 'Return var incorrect from passthru');

		$shell->verify();
	}

	// @TODO (florian): add test_passthru_blank_line_returns_echo_and_new_line_on_unix()
	// and on_windows() once the mocking of Shell has been improved to allow mocking only
	// the passthru functions of the Shell object

	public function test_exec_real()
	{
		$iam = exec('whoami');
		$shell = new Shell();

		$output = array();
		$this->assertEquals($iam, $shell->exec('whoami', $output, $returnVar));
		$this->assertEquals($iam, implode('', $output), 'Real output of whoami unexpected');
		$this->assertSame(0, $returnVar, 'exec of whoami had bad return status');
	}

	public function test_exec_mock()
	{
		$shell = self::createMockShell($this);
		$shell->expectExec('whoami', array('p diddy'), 0, 'mo money, mo problems');

		$lastLine = $shell->exec('whoami', $output, $returnVar);
		$this->assertEquals('p diddy', $output[0], "P Diddy isn't who i am =(");
		$this->assertEquals('mo money, mo problems', $lastLine, 'last line not returned from exec');
		$this->assertSame(0, $returnVar, 'Return var incorrect from exec');

		$shell->verify();
	}

	public function testMockShell_StackedExec()
	{
		$mockShell = self::createMockShell();
		$outputLs = array('file1', 'file2');
		$outputCat = array('No such file', 'Sorry');
		$mockShell
			->expectExec('ls ~/ | xargs echo', $outputLs, $outputLs[1], 0)
			->expectExec('cat README', $outputLs, $outputCat[1], 1);

		$actualCatOutput = array();
		$lastCatLine = '';
		$catExitStatus = $mockShell->exec('cat README', $actualCatOutput, $lastCatLine);

		$this->assertEquals($outputLs, $actualCatOutput, 'output');
		$this->assertEquals('Sorry', $lastCatLine, 'last line of output');
		$this->assertEquals(1, $catExitStatus, 'Exit status');

		$actualLsOutput = array();
		$lastLsLine = '';
		$lsExitStatus = $mockShell->exec('ls ~/ | xargs echo', $actualLsOutput, $lastLsLine);

		$this->assertEquals($outputLs, $actualLsOutput, 'output');
		$this->assertEquals('file2', $lastLsLine, 'last line of output');
		$this->assertEquals(0, $lsExitStatus, 'Exit status');

		$mockShell->verify();
	}

	public function testMockShell_Verify()
	{
		$mockShell = self::createMockShell();
		$mockShell->expectPassthru('ls', 0);

		try
		{
			$mockShell->verify();
			$this->fail('Expected verify to fail');
		}
		catch (\PHPUnit_Framework_ExpectationFailedException $e)
		{
			$this->assertContains('Some MockShell commands not run', $e->getMessage(), "expected message");
		}
	}

	public function test_gethostname()
	{
		$name = gethostname();
		$shell = new Shell();
		$this->assertEquals($name, $shell->gethostname(), 'Hostnames did not match.');
	}

	public function test_file_exists()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$exists = file_exists($filename);
			$phpu->assertFalse($exists, "Expected $filename to not exist prior to test");

			$exists = $shell->file_exists($filename);
			$phpu->assertFalse($exists, "Shell returned wrong result for file_exists");

			touch($filename);
			$exists = file_exists($filename);
			$phpu->assertTrue($exists, "$filename was not created by touch");

			$exists = $shell->file_exists($filename);
			$phpu->assertTrue($exists, "Shell returned wrong result for file_exists");
		});
	}

	public function test_ini_parse()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			file_put_contents($filename,
				'[section1]
variable = value
');
			$global_parsed = parse_ini_file($filename, true);
			$our_parsed = $shell->parse_ini_file($filename, true);

			$phpu->assertEquals($our_parsed, $global_parsed,
				'Parsed files did not match');
		});
	}

	public function testMkdir()
	{
		// Will create sub-directory based on file name in /tmp
		$path = '/tmp/' . __CLASS__ . __FILE__ . __METHOD__;
		try {
			$shell = new Shell();
			$shell->mkdir($path, 0777, true);

			$this->assertTrue(is_dir($path));
			@rmdir($path);
		}
		catch (\Exception $e)
		{
			@rmdir($path);
			throw $e;
		}
	}

	public function test_unlink()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$exists = file_exists($filename);
			$phpu->assertFalse($exists, "Expected $filename to not exist prior to test");

			touch($filename);
			$exists = file_exists($filename);
			$phpu->assertTrue($exists, "$filename was not created by touch");

			$shell->unlink($filename);
			$exists = file_exists($filename);
			$phpu->assertFalse($exists, "$filename not deleted by Shell class");
		});
	}

	public function test_file_put_contents()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$data = 'some random non-array of data';
			$shell->file_put_contents($filename, $data);
			$actual = file_get_contents($filename);
			$phpu->assertEquals($data, $actual, 'File data not written correctly');
		});
	}

	public function test_mktempdir()
	{
		$shell = new Shell();
		$dir = $shell->mktempdir();
		$this->assertTrue(file_exists($dir), 'Temp dir was not created');
	}

	public function test_touch()
	{
		$this->doStuffToTempFile(function(BaseTestCase $phpu, Shell $shell, $filename)
		{
			$shell->touch($filename);
			$phpu->assertTrue(file_exists($filename), 'File was not touched');
		});
	}
}