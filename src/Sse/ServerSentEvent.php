<?php

namespace Qruto\Wave\Sse;

use Stringable;

class ServerSentEvent implements Stringable
{
    public function __construct(
        private string $event,
        private string $data,
        private ?string $id = null,
        private ?int $retry = null
    ) {
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setRetry(int $retry): self
    {
        $this->retry = $retry;

        return $this;
    }

    private function propertyString(string $property): string
    {
        return "$property: ".$this->$property.PHP_EOL;
    }

    public function __toString(): string
    {
        $event = $this->propertyString('event');

        if ($this->retry) {
            $event .= $this->propertyString('retry');
        }

        $event .= $this->propertyString('data');

        if ($this->id) {
            $event .= $this->propertyString('id');
        }

        return $event.PHP_EOL;
    }

    public function __invoke()
    {
        echo $this;

        if (ob_get_level() !== 0) {
            ob_flush();
        }

        flush();
    }

    public function echo(): void
    {
        $this();
    }
}
