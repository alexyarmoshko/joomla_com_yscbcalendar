COMPONENT_NAME := com_yscbcalendar
MANIFEST := yscbcalendar.xml
UPDATE_XML := yscbcalendar.update.xml
INSTALL_DIR := installation

VERSION := $(shell awk -F'[<>]' '/<version>/{print $$3; exit}' $(MANIFEST))

ZIP_VERSION := $(subst .,-,$(VERSION))
ZIP_NAME := $(COMPONENT_NAME)-v$(ZIP_VERSION).zip
ZIP_PATH := $(INSTALL_DIR)/$(ZIP_NAME)

PACKAGE_FILES := $(MANIFEST) site admin media

.PHONY: dist clean

dist: $(ZIP_PATH)
	@SHA256=$$(shasum -a 256 "$(ZIP_PATH)" | awk '{print $$1}'); \
	awk -v sha="$$SHA256" '{ \
		if ($$0 ~ /<sha256>[^<]+<\/sha256>/) { \
			sub(/<sha256>[^<]+<\/sha256>/, "<sha256>" sha "</sha256>"); \
		} \
		print; \
	}' "$(UPDATE_XML)" > "$(UPDATE_XML).tmp" && mv "$(UPDATE_XML).tmp" "$(UPDATE_XML)" && \
	echo "Updated $(UPDATE_XML) sha256 to $$SHA256"

$(ZIP_PATH): $(PACKAGE_FILES)
	@mkdir -p $(INSTALL_DIR)
	@rm -f "$(ZIP_PATH)"
	@cd "$(CURDIR)" && zip -r -X "$(ZIP_PATH)" $(PACKAGE_FILES) -x "*.DS_Store" -x "*/.DS_Store"

clean:
	@rm -f "$(ZIP_PATH)"
