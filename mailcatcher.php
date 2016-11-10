#!/usr/bin/php
<?php
/**
 * @file
 * Sendmail wrapper to discard mails selectively.
 */

require __DIR__ . '/vendor/autoload.php';

$config = parse_ini_file(__DIR__ . '/config.ini');

$pointer = fopen('php://stdin', 'r');
$mail = '';
while ($line = fgets($pointer)) {
  $mail .= $line;
}
$email_parser = new \MS\Email\Parser\Parser();
$message = $email_parser->parse($mail);
$email_recipients = $message->getTo();
foreach ($email_recipients as $mail) {
  if (!empty($config['whitelist'])) {
    $pass = FALSE;
    foreach ($config['whitelist'] as $pattern) {
      if (strstr($mail, $pattern) !== FALSE) {
        $pass = TRUE;
      }
    }
  }
  elseif (!empty($config['blacklist'])) {
    $pass = TRUE;
    foreach ($config['blacklist'] as $pattern) {
      if (strstr($mail, $pattern) !== FALSE) {
        $pass = FALSE;
      }
    }
  }

  if (!$pass) {
    print 'Not allowed';
    exit(1);
  }
}

// We can send the mail, it passed all the checks.
$descriptorspec = array(
  0 => array("pipe", "r"),
  1 => array("pipe", "w"),
);
$pipes = array();

$mail_cmd = isset($config['sendmail']) ? $config['sendmail'] : "/usr/sbin/sendmail -t -i";
$process = proc_open($mail_cmd, $descriptorspec, $pipes);
if (is_resource($process)) {
  fwrite($pipes[0], stream_get_contents(STDIN));
  fclose($pipes[0]);
  fclose($pipes[1]);
  return proc_close($process);
}
