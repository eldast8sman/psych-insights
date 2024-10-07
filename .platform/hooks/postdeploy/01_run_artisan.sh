#!/bin/bash

# Download env.json file from S3 bucket
aws s3 cp s3://psychenv/env.json /tmp/env.json

# Parse env.json and create .env file
cat /tmp/env.json | jq -r 'to_entries[] | "\(.key)=\(.value)"' > /var/app/current/.env

# Download psychinsightsapp.json file from S3 bucket
aws s3 cp s3://psychenv/psychinsightsapp.json /tmp/psychinsightsapp.json

# Create directory if it doesn't exist
mkdir -p storage/app/public/fcm

# Copy psychinsightsapp.json to storage/app/public/fcm folder
cp /tmp/psychinsightsapp.json storage/app/public/fcm/psychinsightsapp.json

# Download AppleKey file from S3 bucket
aws s3 cp s3://psychenv/AppleKey.p8 /tmp/AppleKey.p8

# Create directory if it doesn't exist
mkdir -p storage/app/public/apple

# Copy psychinsightsapp.json to storage/app/public/apple folder
cp /tmp/AppleKey.p8 storage/app/public/apple/AppleKey.p8

# Navigate to the Laravel app directory
cd /var/app/current

# Run Laravel Artisan commands
php artisan migrate
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan storage:link
