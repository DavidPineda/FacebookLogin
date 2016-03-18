create table if not exists 
PREFIX_login_facebook
(
    id_customer int(10) unsigned not null,    
    id_facebook BigInt not null,
    primary key (id_customer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

alter table PREFIX_login_facebook add constraint PREFIX_login_facebook_customer
foreign key (id_customer) references PREFIX_customer (id_customer)
on delete no action
on update no action;