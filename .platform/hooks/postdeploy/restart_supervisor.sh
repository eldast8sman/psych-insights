#!/bin/sh

sudo systemctl restart supervisord
sudo supervisorctl restart all