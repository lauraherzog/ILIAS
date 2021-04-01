#!/bin/bash


#          URL="https://api.github.com/repos/${GITHUB_REPOSITORY}/pulls/${{ github.event.pull_request.number }}/files"
#          CHANGED_FILES=$(curl -s -X GET -G $URL | jq -r '.[] | .filename ' | grep '.php')
#          echo $CHANGED_FILES >> $GITHUB_ENV

echo "Repo: $GH_REPO"
echo "Number: $PR_NUMBER"

exit

for FILE in ${CHANGED_FILES}
do
	echo "Check file: ${FILE}"
	RUNCSFIXER=$(libs/composer/vendor/bin/php-cs-fixer fix --using-cache=no --diff --config=./CI/PHP-CS-Fixer/code-format.php_cs ${FILE})
	RESULT=$?
	if [[ ${RESULT} -ne 0 ]]
	then
		exit ${RESULT}
  fi
done
exit 0