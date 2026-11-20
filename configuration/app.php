<?php
// Path to the Python interpreter used for predictions.
// On Render/Docker the PYTHON_PATH environment variable is set automatically; locally the fallback below is used.
define('PYTHON_PATH', getenv('PYTHON_PATH') ?: 'C:/Users/Djalil/anaconda3/python.exe');
