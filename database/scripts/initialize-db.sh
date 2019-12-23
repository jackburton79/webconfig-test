#!/bin/sh

mysql test -u test < drop_tables.sql
mysql test -u test < create_tables.sql
mysql test -u test < insert_test_values.sql