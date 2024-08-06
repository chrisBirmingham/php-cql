<?php

namespace CassandraNative\Exception;

/**
 * Exception thrown if the current connected user tried something they aren't allowed to do
 */
class UnauthorizedException extends AuthenticationException {}
