# -*- coding: utf-8 -*-
import os

from dotenv import load_dotenv, find_dotenv
from os.path import dirname as parent
from voip.utils import make_dir, INSTANCE_FOLDER_PATH

load_dotenv(find_dotenv())


class BaseConfig(object):
	PROJECT = 'voip-server'

	PROJECT_ROOT = os.path.abspath(parent(parent(__file__)))
	APP_DIR = os.path.join(PROJECT_ROOT, 'core')

	DEBUG = True

	HOST = '0.0.0.0'
	PORT = 5000

	APP_DATE_FORMAT = '%Y-%m-%d %H:%M:%S'

	DB_HOST = os.environ.get('DB_HOST')
	DB_NAME = os.environ.get('DB_NAME')
	DB_USER = os.environ.get('DB_USER')
	DB_PASS = os.environ.get('DB_PASS')
	DB_PORT = os.environ.get('DB_PORT')

	SERVER_KEY = os.environ.get('SERVER_KEY')

	MODEM_RECONNECT_RETRY_COUNT = 5
	MODEM_SLEEP_SECONDS = 5

	SCHEDULE_TIME_INTERVAL = 15

	if os.environ.get('ENV') == 'production':
		API_URL = 'http://voip-api.ccprivate.me/api/'
	else:
		API_URL = 'http://localhost:1122/api/'

	LOG_FOLDER = os.path.join(INSTANCE_FOLDER_PATH, 'logs')
	make_dir(LOG_FOLDER)
