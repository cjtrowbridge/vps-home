#!/bin/bash
clear && tail -fn 50 /var/log/apache2/access.log | grep -vE "$(echo $SSH_CLIENT | awk '{ print $1}')"
