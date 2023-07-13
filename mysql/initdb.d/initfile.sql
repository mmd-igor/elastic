CREATE DATABASE if not exists mw_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER if not exists 'mw_user'@'%' IDENTIFIED BY 'dasljk3JK';
GRANT ALL PRIVILEGES ON mw_db.* TO 'mw_user'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;

create table if not exists mgroups (
    code varchar(16) primary key, 
    name varchar(128)
);
create table if not exists materials (
    code varchar(50) primary key, 
    name varchar(200), mgroup varchar(16),
    FOREIGN KEY (mgroup) REFERENCES mgroups(code)
);
create table if not exists wclasses (
    code varchar(16) primary key, 
    name varchar(128)
);
create table if not exists works (
    code varchar(16) primary key, 
    name varchar(200), 
    wclass varchar(16),
    FOREIGN KEY (wclass) REFERENCES wclasses(code)
);
create table if not exists material_work(
    mcode varchar(50),
    wcode varchar(32),
    primary key(mcode, wcode),
    foreign key (mcode) references materials(code),
    foreign key (wcode) references works(code)
);

create table if not exists test(
    mcode varchar(50),
);