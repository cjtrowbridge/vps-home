#!/bin/bash

# Path to the CSV file
csv_file="/path/to/your/folder/usage.csv"

# Check if the CSV file already exists
if [ ! -f "$csv_file" ]; then
    # Create header for the CSV file
    echo "Timestamp,CPUPercentage,RAMPercentage,DiskUsage,NetworkUp,NetworkDown" > "$csv_file"
fi

# Get current Unix timestamp
timestamp=$(date +%s)

# Get CPU usage percentage
cpu_percentage=$(top -bn 1 | grep "%Cpu(s)" | awk '{print $2}')

# Get RAM usage percentage
ram_percentage=$(free | awk '/Mem/{printf("%.2f\n", ($3/$2) * 100)}')

# Get disk usage for the / partition (change /dev/sdXY to match your partition)
disk_usage=$(df -h | awk '/\/dev\/sdXY/{print $5}' | sed 's/%//')

# Get network up/down utilization in MB/sec
network_up=$(cat /proc/net/dev | awk '/eth0/{print $10/1024/1024}')
network_down=$(cat /proc/net/dev | awk '/eth0/{print $2/1024/1024}')

# Append data to CSV file
echo "$timestamp,$cpu_percentage,$ram_percentage,$disk_usage,$network_up,$network_down" >> "$csv_file"
