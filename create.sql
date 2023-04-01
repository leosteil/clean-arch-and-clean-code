drop table cccat10.product;
drop table cccat10.coupon;

drop schema cccat10;

create schema cccat10;

create table cccat10.product (
    id_product integer,
    description text,
    price numeric,
    width integer,
    height integer,
    length integer,
    weight numeric
);

insert into cccat10.product (id_product, description, price, width, height, length, weight) values (1, 'A', 1000, 100, 30, 10, 3);
insert into cccat10.product (id_product, description, price, width, height, length, weight) values (2, 'B', 5000, 50, 50, 50, 22);
insert into cccat10.product (id_product, description, price, width, height, length, weight) values (3, 'C', 30, 10, 10, 10, 0.9);
insert into cccat10.product (id_product, description, price, width, height, length, weight) values (4, 'D', 30, -10, 10, 10, 0.9);
insert into cccat10.product (id_product, description, price, width, height, length, weight) values (5, 'E', 30, 10, 10, 10, -1);


create table cccat10.coupon (
    coupon_code text,
    discount numeric,
    expires_at date
);

insert into cccat10.coupon (coupon_code, discount, expires_at) values ('20off', 20, '2024-03-14');
insert into cccat10.coupon (coupon_code, discount, expires_at) values ('10off', 20, '2022-03-14');
