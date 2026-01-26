<?php

class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $secure;
    private $timeout = 30;
    private $debug = false;
    private $logBuffer = [];
    private $socket;
    private $lastError = null;

    public function getLastError() {
        return $this->lastError;
    }

    public function getLogs() {
        return implode("\n", $this->logBuffer);
    }

    public function clearLogs() {
        $this->logBuffer = [];
    }

    public function __construct($config) {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->secure = $config['secure'];
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    private function log($message) {
        $this->logBuffer[] = "[" . date('H:i:s') . "] " . $message;
        if ($this->debug) {
            echo htmlspecialchars($message) . "<br>\n";
        }
    }

    private function getResponse() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        $this->log("SERVER: " . $response);
        return $response;
    }

    private function sendCommand($command, $expectCode = 250) {
        $this->log("CLIENT: " . $command);
        fputs($this->socket, $command . "\r\n");
        $response = $this->getResponse();
        
        $code = (int)substr($response, 0, 3);
        if ($code !== $expectCode) {
            throw new Exception("SMTP Error: Expected $expectCode but got $response");
        }
        return $response;
    }

    public function send($to, $subject, $body, $fromEmail, $fromName, $isHtml = true) {
        try {
            $protocol = ($this->secure === 'ssl') ? 'ssl://' : ''; // tls handled after connection
            $host = $protocol . $this->host;
            
            $this->socket = fsockopen($host, $this->port, $errno, $errstr, $this->timeout);
            
            if (!$this->socket) {
                throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
            }

            $this->getResponse(); // Banner
            $this->sendCommand("EHLO " . gethostname());

            if ($this->secure === 'tls') {
                $this->sendCommand("STARTTLS", 220);
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                stream_socket_enable_crypto($this->socket, true, $cryptoMethod);
                $this->sendCommand("EHLO " . gethostname());
            }

            $this->sendCommand("AUTH LOGIN", 334);
            $this->sendCommand(base64_encode($this->username), 334);
            $this->sendCommand(base64_encode($this->password), 235);

            $this->sendCommand("MAIL FROM: <" . $this->username . ">");
            $this->sendCommand("RCPT TO: <" . $to . ">");
            $this->sendCommand("DATA", 354);

            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Date: " . date("r");
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <" . $fromEmail . ">";
            $headers[] = "To: <" . $to . ">";
            $headers[] = "Reply-To: <" . $fromEmail . ">";
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
            
            // Unique Message-ID matches the behavior of professional mailers
            $hostDomain = parse_url($host, PHP_URL_HOST) ?? $this->host;
            $messageId = sprintf("<%s.%s@%s>", base64_encode(uniqid()), time(), $hostDomain);
            $headers[] = "Message-ID: " . $messageId;
            $headers[] = "X-Mailer: AIF CRM Mailer v1.0";
            $headers[] = "X-Priority: 3"; // Normal priority
            
            if ($isHtml) {
                $boundary = "=_" . md5(uniqid(time()));
                $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
                $headers[] = "MIME-Version: 1.0";

                // Plain text version
                $plainText = strip_tags(str_replace(['<br>', '<br/>', '</p>'], ["\n", "\n", "\n\n"], $body));
                
                $messageBody = "--$boundary\r\n";
                $messageBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $messageBody .= chunk_split(base64_encode($plainText)) . "\r\n";

                // HTML version
                $messageBody .= "--$boundary\r\n";
                $messageBody .= "Content-Type: text/html; charset=UTF-8\r\n";
                $messageBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $messageBody .= chunk_split(base64_encode($body)) . "\r\n";

                $messageBody .= "--$boundary--";
                
                $body = $messageBody;
            } else {
                $headers[] = "Content-Type: text/plain; charset=UTF-8";
            }

            $emailContent = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
            $this->sendCommand($emailContent);
            
            $this->sendCommand("QUIT", 221);
            fclose($this->socket);
            
            return true;

        } catch (Exception $e) {
            if ($this->socket) fclose($this->socket);
            $this->lastError = $e->getMessage();
            $this->log("ERROR: " . $e->getMessage());
            // Log error to file system for debugging if needed
            error_log($e->getMessage());
            return false;
        }
    }
}
