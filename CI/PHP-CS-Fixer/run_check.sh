#!/bin/bash
source CI/Import/Functions.sh
source CI/Import/Variables.sh

$PHP_CS_FIXER fix --config=./CI/PHP-CS-Fixer/code-format.php_cs --dry-run -vvv
PIPE_EXIT_CODE=`echo ${PIPESTATUS[0]}`
exit $PIPE_EXIT_CODE