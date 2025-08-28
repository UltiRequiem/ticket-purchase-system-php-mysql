<?php
namespace TicketFairy\Exceptions;

abstract class TicketException extends \Exception {
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class InsufficientTicketsException extends TicketException {
    public function __construct(string $message = "Not enough tickets available", int $code = 400, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class InvalidInputException extends TicketException {
    public function __construct(string $message = "Invalid input provided", int $code = 400, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

class EventNotFoundException extends TicketException {
    public function __construct(string $message = "Event not found", int $code = 404, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}