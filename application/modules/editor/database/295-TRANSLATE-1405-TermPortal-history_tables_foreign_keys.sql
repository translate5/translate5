ALTER TABLE `terms_term_history`  
  ADD CONSTRAINT `collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `termEntryId` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `termId` FOREIGN KEY (`termId`) REFERENCES `terms_term`(`id`) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE `terms_attributes_history`  
  ADD CONSTRAINT `tah_collectionId` FOREIGN KEY (`collectionId`) REFERENCES `LEK_languageresources`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `tah_termEntryId` FOREIGN KEY (`termEntryId`) REFERENCES `terms_term_entry`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `tah_termId` FOREIGN KEY (`termId`) REFERENCES `terms_term`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `tah_attrId` FOREIGN KEY (`attrId`) REFERENCES `terms_attributes`(`id`) ON UPDATE CASCADE ON DELETE CASCADE;