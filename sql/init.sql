drop database if exists api_db;
create database api_db;
use api_db;

create table users(
  id int primary key auto_increment,
  created datetime not null default current_timestamp,
  validated boolean not null default false,
  email varchar(45) unique not null,
  password varchar(64) not null,
  auth_lvl  int not null default 1
);

create table profiles(
  id int primary key auto_increment,
  user_id int unique  not null references users(id),
  name varchar(20) not null,
  surname varchar(25) not null
);

create table auth_keys(
  id int primary key auto_increment,
  created datetime not null default current_timestamp,
  user_id int references users(id),
  auth_key varchar(64) not null
);

create table db_logs(
  id int primary key auto_increment not null,
  created datetime not null default current_timestamp,
  table_name varchar(20) not null,
  user_id int not null references users(id),
  ip_addr varchar(12) not null,
  query text not null,
  data  text default null
);

create table request_logs(
  id int primary key auto_increment not null,
  created datetime not null default current_timestamp,
  user_id int not null references users(id),
  ip_addr varchar(12) not null,
  request text not null,
  data  text default null
);
