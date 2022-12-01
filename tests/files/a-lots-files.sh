#!/bin/bash
for i in {0001..1000}
do
  cat ./regina.png > "1file_${i}.png"
done
