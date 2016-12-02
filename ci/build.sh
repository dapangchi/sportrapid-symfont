#!/bin/bash
PROJ_ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")"/.. && pwd)
BIN="${PROJ_ROOT}/bin"
SRC="${PROJ_ROOT}/src"
SUMMARY=""
ERROR=0

function run_tool() {
    echo -e "\e[1;34m*********************************************"
    echo -e "\e[1;34m*\e[0;33m ${2}"
    echo -e "\e[1;34m*********************************************\e[0m"
    echo
    ${1}

    if [ $? != 0 ]
    then
        SUMMARY="${SUMMARY}\e[1;31m"
        ERROR=1
    else
        SUMMARY="${SUMMARY}\e[1;32m"
    fi

    SUMMARY="${SUMMARY}\u25cf\e[0m $2\n"
    echo
}

run_tool "${BIN}/parallel-lint ${SRC}" "PHP Lint"
run_tool "${BIN}/php-cs-fixer -v --config-file=${PROJ_ROOT}/.php_cs fix" "PHP Coding Standards Fixer"
run_tool "${BIN}/var-dump-check --ladybug ${SRC}" "Vardump Checker"
run_tool "${PROJ_ROOT}/app/console security:check" "Security Check"
run_tool "${BIN}/phploc --progress ${SRC}" "PHP Lines of Code"


echo -e "\e[1;34m*********************************************"
echo -e "\e[1;34m*\e[0;33m Summary"
echo -e "\e[1;34m*********************************************\e[0m"
echo -e ${SUMMARY}

exit ${ERROR}
