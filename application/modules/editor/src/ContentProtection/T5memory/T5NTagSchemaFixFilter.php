<?php

namespace MittagQI\Translate5\ContentProtection\T5memory;

class T5NTagSchemaFixFilter extends \php_user_filter
{
    protected $buffer = '';

    protected $status = PSFS_FEED_ME;

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;

            if (PSFS_PASS_ON == $this->status) {
                // we're already done, just copy the content
                stream_bucket_append($out, $bucket);

                continue;
            }

            $this->buffer .= $bucket->data;
            if ($this->fix()) {
                // first element found
                // send the current buffer
                $bucket->data = $this->buffer;
                $bucket->datalen = strlen($bucket->data);
                stream_bucket_append($out, $bucket);
                $this->buffer = null;
                // no need for further processing
                $this->status = PSFS_PASS_ON;
            }
        }

        return $this->status;
    }

    /**
     * looks for the first (root) element in $this->buffer
     * if it doesn't contain a xsi namespace decl inserts it
     */
    protected function fix()
    {
        $rc = false;
        if (preg_match('!<([^?>\s]+)\s?([^>]*)>!', $this->buffer, $m, PREG_OFFSET_CAPTURE)) {
            $rc = true;
            if (! str_contains($m[2][0], 'xmlns:t5')) {
                $in = '<' . $m[1][0]
                    . ' xmlns:t5="http://www.w3.org/2001/XMLSchema-instance" '
                    . $m[2][0] . '>';
                $this->buffer = substr($this->buffer, 0, $m[0][1])
                    . $in
                    . substr($this->buffer, (int) $m[0][1] + strlen($m[0][0]));
            }
        }

        return $rc;
    }
}
