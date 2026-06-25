@echo off
REM ============================================
REM  SCHF - Performance Tests
REM ============================================

echo [1/3] Running REST API benchmark...
docker run --rm --network host -v "%~dp0scripts\perf\results:/scripts/perf/results" schf-k6 run /scripts/perf/rest-benchmark.js

echo.
echo [2/3] Running stress test...
docker run --rm --network host -v "%~dp0scripts\perf\results:/scripts/perf/results" schf-k6 run /scripts/perf/stress-test.js

echo.
echo [3/3] Results saved to scripts\perf\results\
echo Done!


