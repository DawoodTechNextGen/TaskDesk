<?php
/**
 * Minimal IMAP client over a TLS socket. Only what cron_process_bounces.php
 * needs: search, fetch a raw message and set a keyword. Written against the
 * protocol directly so it works whether or not the PHP imap extension is
 * installed (it was removed from PHP 8.4).
 */
class ImapClient {
    private $sock;
    private $tag = 0;

    public function __construct($host, $port, $user, $pass, $timeout = 20) {
        $this->sock = @stream_socket_client("ssl://$host:$port", $errno, $errstr, $timeout);
        if (!$this->sock) {
            throw new RuntimeException("IMAP connect to $host:$port failed: $errstr ($errno)");
        }
        stream_set_timeout($this->sock, $timeout);
        $greeting = fgets($this->sock);
        if ($greeting === false || strpos($greeting, '* OK') !== 0) {
            throw new RuntimeException('IMAP greeting failed: ' . trim((string)$greeting));
        }
        $this->command('LOGIN ' . $this->quote($user) . ' ' . $this->quote($pass));
    }

    /** EXAMINE opens the mailbox read-only, so nothing can be flagged. */
    public function select($mailbox = 'INBOX', $readOnly = false) {
        $this->command(($readOnly ? 'EXAMINE ' : 'SELECT ') . $this->quote($mailbox));
    }

    /** Returns the UIDs matching an IMAP SEARCH criteria string. */
    public function search($criteria) {
        $uids = [];
        foreach ($this->command('UID SEARCH ' . $criteria) as $line) {
            if (strpos($line, '* SEARCH') === 0) {
                foreach (preg_split('/\s+/', trim(substr($line, 8))) as $uid) {
                    if ($uid !== '') {
                        $uids[] = (int)$uid;
                    }
                }
            }
        }
        return $uids;
    }

    /** Raw RFC 822 source of one message, without setting \Seen. */
    public function fetchRaw($uid) {
        $lines = $this->command('UID FETCH ' . (int)$uid . ' (BODY.PEEK[])');
        return $lines['__literal'] ?? '';
    }

    public function addFlag($uid, $flag) {
        $this->command('UID STORE ' . (int)$uid . ' +FLAGS (' . $flag . ')');
    }

    public function logout() {
        if ($this->sock) {
            try {
                $this->command('LOGOUT');
            } catch (Throwable $e) {
                // Closing anyway
            }
            fclose($this->sock);
            $this->sock = null;
        }
    }

    private function quote($s) {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"';
    }

    /**
     * Sends a tagged command and reads until its tagged reply. Untagged lines are
     * returned in order; the first {n} literal is returned under '__literal'.
     */
    private function command($cmd) {
        $tag = 'A' . (++$this->tag);
        fwrite($this->sock, "$tag $cmd\r\n");
        $out = [];
        while (true) {
            $line = fgets($this->sock);
            if ($line === false) {
                throw new RuntimeException('IMAP connection closed while waiting for ' . strtok($cmd, ' '));
            }
            if (preg_match('/\{(\d+)\}\r\n$/', $line, $m)) {
                $len = (int)$m[1];
                $data = '';
                while (strlen($data) < $len) {
                    $chunk = fread($this->sock, min(8192, $len - strlen($data)));
                    if ($chunk === false || $chunk === '') {
                        throw new RuntimeException('IMAP connection closed while reading a literal');
                    }
                    $data .= $chunk;
                }
                if (!isset($out['__literal'])) {
                    $out['__literal'] = $data;
                }
                $out[] = rtrim($line);
                continue;
            }
            if (strpos($line, "$tag ") === 0) {
                if (strpos($line, "$tag OK") !== 0) {
                    // Never echo the LOGIN command, it carries the password
                    $what = stripos($cmd, 'LOGIN') === 0 ? 'LOGIN' : $cmd;
                    throw new RuntimeException("IMAP $what failed: " . trim($line));
                }
                return $out;
            }
            $out[] = rtrim($line);
        }
    }
}
