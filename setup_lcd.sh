#!/bin/bash

#From http://www.lcdwiki.com/MHS-3.5inch_RPi_Display
sudo rm -rf LCD-show
git clone https://github.com/goodtft/LCD-show.git
chmod -R 755 LCD-show
cd LCD-show/
sudo ./MHS35-show
