<?php

namespace SfCod\QueueBundle\Base;

use DateInterval;
use DateTime;
use DateTimeInterface;

/**
 * Trait TimeTrait
 *
 * @author Virchenko Maksim <muslim1992@gmail.com>
 *
 * @package SfCod\QueueBundle\JobContract
 */
trait InteractWithTimeTrait
{
    /**
     * Get the number of seconds until the given DateTime.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     *
     * @return int
     */
    protected function secondsUntil($delay): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? max(0, $delay->getTimestamp() - $this->currentTime())
            : (int)$delay;
    }

    /**
     * Get the "available at" UNIX timestamp.
     *
     * @param DateTimeInterface|DateInterval|int $delay
     *
     * @return int
     */
    protected function availableAt($delay = 0): int
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? $delay->getTimestamp()
            : (new DateTime())->add(new DateInterval(sprintf('PT%dS', $delay)))->getTimestamp();
    }

    /**
     * If the given value is an interval, convert it to a DateTime instance.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     *
     * @return \DateTimeInterface|int
     */
    protected function parseDateInterval($delay)
    {
        if ($delay instanceof DateInterval) {
            $delay = (new DateTime())->add($delay);
        }

        return $delay;
    }

    /**
     * Get the current system time as a UNIX timestamp.
     *
     * @return int
     */
    protected function currentTime(): int
    {
        return time();
    }
}
