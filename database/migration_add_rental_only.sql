
USE clothyyy;

ALTER TABLE products 
ADD COLUMN rental_only TINYINT(1) NOT NULL DEFAULT 0 AFTER is_rentable;



