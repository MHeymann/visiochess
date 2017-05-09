#!/bin/bash

ls php/ | grep 'test' > .tests.php

while read FILE
do
	phpunit "php/$FILE"
done < .tests.php
