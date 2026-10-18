-- Jalankan hanya jika Anda mempertahankan database lama dan kolom snap_token belum ada.
USE pesantren_spp;
ALTER TABLE payments ADD COLUMN snap_token VARCHAR(255) NULL AFTER gateway_status;
