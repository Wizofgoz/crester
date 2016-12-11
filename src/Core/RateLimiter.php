<?php
namespace Crester\Core;
class RateLimiter
{
    protected $timestamp = 0;
    protected $calls = 0;
    protected $limit;
    protected $frequency;
    /**
     * Create new RateLimiter
     * Default 60 requests every 60 seconds
     *
     * @param integer $limit Optional calls per frequency
     * @param integer $frequency Optional frequency in seconds
     */
    public function __construct($limit = 60, $frequency = 60) {
        $this->limit = $limit;
        $this->frequency = $frequency;
    }
    /**
     * Call before every API request
     */
    public function limit() {
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
?>
