<?php
/*
*   MIT License
*
*   Copyright (c) 2016 Wizofgoz
*
*   Permission is hereby granted, free of charge, to any person obtaining a copy
*   of this software and associated documentation files (the "Software"), to deal
*   in the Software without restriction, including without limitation the rights
*   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
*   copies of the Software, and to permit persons to whom the Software is
*   furnished to do so, subject to the following conditions:
*
*   The above copyright notice and this permission notice shall be included in all
*   copies or substantial portions of the Software.
*
*   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
*   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
*   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
*   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
*   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
*   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
*   SOFTWARE.
*/

namespace Crester\Core;

class RateLimiter
{
    protected $timestamp = 0;
    protected $calls = 0;
    protected $limit;
    protected $frequency;

    /**
     * Create new RateLimiter
     * Default 60 requests every 60 seconds.
     *
     * @param int $limit     Optional calls per frequency
     * @param int $frequency Optional frequency in seconds
     */
    public function __construct($limit = 60, $frequency = 60)
    {
        $this->limit = $limit;
        $this->frequency = $frequency;
    }

    /**
     * Call before every API request.
     */
    public function limit()
    {
        // Increment call counter every request
        $this->calls++;
        // Allow burst of requests until it reaches limit threshold
        if ($this->calls >= $this->limit) {
            $now = microtime(true);
            // Determine time taken
            $duration = $now - $this->timestamp;
            // Check if we have requested limit requests too fast
            if ($duration < $this->frequency) {
                // Wait before allowing script to continue sending requests
                $wait = ($this->frequency - ($now - $this->timestamp)) * 1000000;
                usleep($wait);
            }
            // Reset current timestamp
            $this->timestamp = microtime(true);
            // Reset call counter
            $this->calls = 0;
        }
    }
}
