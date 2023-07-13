echo -ne \
"[mysql]\n"\
"user=root\n"\
"password=${MYSQL_ROOT_PASSWORD}\n"\
> /root/.my.cnf;
chmod 0600 /root/.my.cnf
exec "$@"
