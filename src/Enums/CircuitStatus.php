<?php

namespace AlgoYounes\CircuitBreaker\Enums;

enum CircuitStatus: string
{
    case OPEN = 'open'; // The circuit breaker is open when the number of failures exceeds the threshold
    case CLOSED = 'closed'; // The circuit breaker is closed when the number of failures is below the threshold
    case HALF_OPEN = 'half-open'; // The circuit breaker is half-open when the number of failures is below the threshold and the circuit breaker is in a cooldown period
}
