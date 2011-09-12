<?php

/**
 * Generic class for just-in-time daemonized services.
 */
abstract class BackgroundService {
  protected $pid_file_name;
  protected $output_file_name;

  protected function startProcess($command) {
    echo 'Starting background service: ' . $command . PHP_EOL;

    // Store the output of the background service and it's pid in temporary
    // files in the system tmp directory.
    $directory = sys_get_temp_dir() . '/background-services';
    @mkdir($directory);
    $unique_file = tempnam($directory, 'background-service-');
    $this->pid_file_name =  $unique_file . '.pid';
    $this->output_file_name = $unique_file . '.out';
    unlink($unique_file);
    exec(sprintf("%s > %s 2>&1 & echo $! > %s", $command, $this->output_file_name, $this->pid_file_name));

    $remaining_tries = 60;
    while ($remaining_tries > 0 && !$this->isReady()) {
      sleep(2);
      --$remaining_tries;
    }

    if ($remaining_tries == 0) {
      echo 'Background service failed:' . PHP_EOL;
      echo $this->getOutput();
      throw new Exception('Background service did not start.');
    }
  }

  protected function isReady() {
    return TRUE;
  }

  public function getOutput() {
    return file_get_contents($this->output_file_name);
  }

  public function getPid() {
    return isset($this->pid_file_name) ? file_get_contents($this->pid_file_name) : NULL;
  }

  public function __destruct() {
    $pid = $this->getPid();
    if (isset($pid)) {
      echo 'Killing background service ' . $this->output_file_name . PHP_EOL;
      exec('kill ' . $pid);
      unlink($this->pid_file_name);
    }
    unlink($this->output_file_name);
  }
}

/**
 * Interface for an X Windows server.
 */
interface XWindowsServiceInterface {
  public function getDisplay();

  public function getWidth();

  public function getHeight();
}

/**
 * Starts an X Windows virtual frame buffer.
 */
class XvfbBackgroundService extends BackgroundService implements XWindowsServiceInterface {
  protected $displayNumber;
  protected $width;
  protected $height;

  public function __construct($display_number, $width = 1600, $height = 1200) {
    $this->displayNumber = $display_number;
    $this->width         = $width;
    $this->height        = $height;

    $command = '/usr/bin/Xvfb :' . $this->displayNumber . ' -ac -screen 0 ' . $this->width . 'x' . $this->height . 'x24';
    $this->startProcess($command);
  }

  public function getDisplay() {
    return ':' . $this->displayNumber;
  }

  public function getWidth() {
    return $this->width;
  }

  public function getHeight() {
    return $this->height;
  }
}

interface SeleniumServiceInterface {
  public function getHost();

  public function getPort();
}

/**
 * Starts an instance of Selenium RC.
 */
class SeleniumBackgroundService extends BackgroundService implements SeleniumServiceInterface {
  protected $port;

  public function __construct(XWindowsServiceInterface $display, $port) {
    $this->port = $port;

    $command = 'DISPLAY="' . $display->getDisplay() . '" && java -jar ~/selenium-server-*/selenium-server.jar -port ' . $this->port;

    $this->startProcess($command);
  }

  public function getHost() {
    return '127.0.0.1';
  }

  public function getPort() {
    return $this->port;
  }

  protected function isReady() {
    return selenium_is_running($this->getHost(), $this->getPort());
  }
}

class SeleniumExternalService implements SeleniumServiceInterface {
  protected $host;
  protected $port;

  public function __construct($host, $port) {
    $this->host = $host;
    $this->port = $port;
  }

  public function getHost() {
    return $this->host;
  }

  public function getPort() {
    return $this->port;
  }
}

/**
 * Starts an X Windows virtual frame buffer.
 */
class ScreencastBackgroundService extends BackgroundService {
  public function __construct(XWindowsServiceInterface $display, $video_file_name) {
    // -an     No audio.
    // -f      Force format.
    // -y      Overwrite output files.
    // -r      Frame rate
    $command = 'ffmpeg -an -f x11grab -y -r 5 -s ' . $display->getWidth() . 'x' . $display->getHeight() . ' -i ' . $display->getDisplay() . '.0+0,0 -vcodec mpeg4 -sameq ' . $video_file_name;
    $this->startProcess($command);
  }

  public function __destruct() {
    parent::__destruct();
    echo 'Sleeping for 3 seconds to allow ffmpeg to cleanly shut down before X does' . PHP_EOL;
    sleep(3);
  }
}

/**
 * Selenium test.
 */
class SeleniumTest {
  protected $bootstrapFile;
  protected $testClassName;
  protected $testFileName;

  public function __construct(SeleniumServiceInterface $selenium_server, $test_file, $base_url, $browser = '*firefox') {
    $this->testFileName = $test_file;
    $this->testClassName = pathinfo($test_file, PATHINFO_FILENAME);
    
    // Construct a bootstrap file for each test, that contains global variables
    // for the Selenium server to connect to, as well as the browser and URL to
    // run against. The alternative to this would be to pass command line
    // parameters to phpunit call in $this->run(). This method seems a tiny bit
    // cleaner.
    $text = '<?php' . PHP_EOL;
    $text .= '$selenium_server_host = \'' . $selenium_server->getHost() . '\';' . PHP_EOL;
    $text .= '$selenium_server_port = ' . $selenium_server->getPort() . ';' . PHP_EOL;
    $text .= '$target_browser = \'' . $browser . '\';' . PHP_EOL;
    $text .= '$target_url = \'' . $base_url . '\';' . PHP_EOL;

    // Store a unique bootstrap file per test.
    $directory = sys_get_temp_dir() . '/selenium-bootstrap';
    @mkdir($directory);
    $this->bootstrapFile = tempnam($directory, 'bootstrap-');

    // echo 'Storing bootstrap: ' . $this->bootstrapFile . PHP_EOL;
    // echo 'Contents: ' . $text . PHP_EOL;

    file_put_contents($this->bootstrapFile, $text);
  }

  public function run($results_file) {
    $old_working_directory = getcwd();
    chdir(dirname($this->testFileName));
    $command = 'phpunit --log-junit ' . $results_file . ' --bootstrap ' . $this->bootstrapFile . ' ' . basename($this->testFileName);
    echo 'Running: ' . $command . PHP_EOL;
    $output = shell_exec($command);
    echo $output;
    chdir($old_working_directory);
  }

  public function getTestClassName() {
    return $this->testClassName;
  }

  public function __destruct() {
    unlink($this->bootstrapFile);
  }
}

function selenium_get_all_tests($directory, SeleniumServiceInterface $selenium, $base_url) {
  $tests = array();
  $test_files = explode(PHP_EOL, trim(shell_exec('find ' . $directory . ' | grep Test.php$')));
  if (!empty($test_files) && !empty($test_files[0])) {
    foreach ($test_files as $test_file) {
      $tests[] = new SeleniumTest($selenium, $test_file, $base_url);
    }
  }
  return $tests;
}

function selenium_is_running($host, $port) {
  echo 'Checking if Selenium is active at ' . $host . ':' . $port . PHP_EOL;
  $success = FALSE;
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'http://' . $host . ':' . $port . '/selenium-server/driver/?cmd=testComplete');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if (curl_exec($ch) !== FALSE) {
    $success = TRUE;
  }
  curl_close($ch);
  return $success;
}
