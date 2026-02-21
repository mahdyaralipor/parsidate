cat > src/Exceptions/InvalidDateExceptions.php << 'PHP'
<?php
declare(strict_types=1);
namespace ParsiDate\Exceptions;

class InvalidDateException extends \InvalidArgumentException {}
PHP

cat > src/Exceptions/InvalidFormatException.php << 'PHP'
<?php
declare(strict_types=1);
namespace ParsiDate\Exceptions;

class InvalidFormatException extends \InvalidArgumentException {}
PHP

echo "Exceptions done"