
USE clothyyy;

-- Add gender column if it doesn't exist
ALTER TABLE categories 
ADD COLUMN IF NOT EXISTS gender ENUM('men', 'women') DEFAULT NULL;







