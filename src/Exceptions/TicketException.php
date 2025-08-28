<?php
namespace TicketFairy\Exceptions;

abstract class TicketException extends \Exception {}

class InsufficientTicketsException extends TicketException {}
class InvalidInputException extends TicketException {}
class EventNotFoundException extends TicketException {}