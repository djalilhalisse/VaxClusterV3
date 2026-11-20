<?php
// Path to the Python interpreter used for predictions.
// On Render/Docker the PYTHON_PATH environment variable is set automatically; locally the fallback below is used.
define('PYTHON_PATH', getenv('PYTHON_PATH') ?: 'C:/Users/Djalil/anaconda3/python.exe');
// Run the three models in parallel (faster). Set PREDICT_PARALLEL=0 on low-memory hosting such as Render's free tier.
define('PREDICT_PARALLEL', getenv('PREDICT_PARALLEL') === false ? true : getenv('PREDICT_PARALLEL') === '1');
