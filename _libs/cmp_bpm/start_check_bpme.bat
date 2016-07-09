@echo off
@rem TODO 在生产环境要定期退出下，在shell做个循环.
@echo for local dev, please check php.exe at PATH
@echo -------------------------------------------
:abc
php start_check_bpme.php
goto abc
