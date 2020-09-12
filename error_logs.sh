#!/bin/bash
clear && tail -fn 50 /var/log/apache2/error.log | grep -vE "$(echo $SSH_CLIENT | awk '{ print $1}')"
