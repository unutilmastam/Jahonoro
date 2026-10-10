-- JAHONORO — 2-versiya: kech vaqti, to'y egasining uyi va uning xaritadagi nuqtasi.
-- phpMyAdmin → itcode_jahonoro → Import. Bir marta import qilinadi.

SET NAMES utf8mb4;

ALTER TABLE events
  ADD COLUMN evening_time   TIME NULL AFTER abet_time,
  ADD COLUMN home_address   VARCHAR(255) NOT NULL DEFAULT '' AFTER district,
  ADD COLUMN home_latitude  DECIMAL(9,6) NULL AFTER home_address,
  ADD COLUMN home_longitude DECIMAL(9,6) NULL AFTER home_latitude;

-- Avvalgi "Boshlanish" vaqti endi "Kech" hisoblanadi
UPDATE events SET evening_time = start_time WHERE evening_time IS NULL;
