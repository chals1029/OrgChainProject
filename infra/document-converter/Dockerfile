FROM debian:trixie-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libreoffice-writer poppler-utils fonts-dejavu-core fonts-liberation \
        fonts-crosextra-carlito fonts-crosextra-caladea \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd --gid 1000 converter \
    && useradd --uid 1000 --gid converter --no-create-home converter

ENV HOME=/work LANG=C.UTF-8
USER converter
WORKDIR /work
