#!/bin/dash

# SELENIUM STARTER FOR JENKINS
# Run this file in a Jenkins job with only parameter for URL:
#     path/to/zoetrope/seleniumstart.sh -t "path/to/tests" -u "http://yoursite.example.com"
#
# When debugging, on might want to add "-o full" to get more output
#     path/to/zoetrope/seleniumstart.sh -t "path/to/tests" -u "http://yoursite.example.com" -o full

# Path to Zoetrope
# Example: /var/lib/jenkints/job/MyJob/workspace/path/to/zoetrope
ABS_PATH=$(cd "$(dirname "$0")"; pwd)

EXEC_FILE="$ABS_PATH/run-selenium.php"
RESULTS_DIR="$ABS_PATH/results"
#TESTS_DIR="$WORKSPACE/test/selenium"
VIDEO_URL="https://jenkins.example.com/video/${JOB_NAME}/${BUILD_NUMBER}/"
SCREENSHOT_URL="https://jenkins.example.com/job/${JOB_NAME}/${BUILD_NUMBER}/HTML_Report"

#echo "Base dir:    $BASE_DIR"
#echo "Exec file:   $EXEC_FILE"
#echo "Results dir: $RESULTS_DIR"
#echo "Tests dir:   $TESTS_DIR"
#echo "Video url:   $VIDEO_URL"

# Execute with a lot of default arguments and add the rest of the arguements at the end
php $EXEC_FILE -bc \
 -r "$RESULTS_DIR" \
 -p random \
 -e "$VIDEO_URL" \
 --resolution "988x1760" \
 --include-path "$ABS_PATH/classes/" \
 --printer Zoetrope_PHPUnitTestListenerTestdox \
 --ss "$SCREENSHOT_URL" \
 -o simple \
 "$@"
 

# -t "$TESTS_DIR" \
# --browser "*firefox /var/lib/jenkins/firefox7.0.1/firefox-bin" \
