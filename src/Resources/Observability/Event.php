<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Resources\Observability;

use InvalidArgumentException;

/**
 * Class Event
 *
 * Represents a discrete, point-in-time occurrence within a trace.
 * Unlike spans or generations, events don't have duration and are used
 * to mark specific moments or actions in the execution flow.
 */
class Event extends AbstractObservability
{
    protected string $type = 'event';

    public function __construct(
        public string $traceId,
        public string $name,
        public string $level = 'DEFAULT',
        public ?array $metadata = null,
        public mixed $input = null,
        public ?string $statusMessage = null
    ) {
        parent::__construct($traceId, $name, $metadata, $input);

        $this->setLevel($level);
    }

    /**
     * Events don't have an end time, so this is a no-op that returns self
     * for compatibility with the interface
     */
    public function end(?array $data = null): static
    {
        // Events mark a point-in-time, so we won't force an endTime unless needed.
        if ($data !== null) {
            $this->update($data);
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setLevel(string $level): static
    {
        $validLevels = ['DEFAULT', 'DEBUG', 'WARNING', 'ERROR'];

        if (! in_array(strtoupper($level), $validLevels)) {
            throw new InvalidArgumentException('Invalid event level');
        }

        $this->level = strtoupper($level);

        return $this;
    }

    public function toArray(): array
    {
        $base = parent::toArray();
        $base['level'] = $this->level;
        $base['statusMessage'] = $this->statusMessage;

        return $base;
    }
}
