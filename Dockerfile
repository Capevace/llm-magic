FROM python:3.12-slim-trixie AS python-image
COPY --from=ghcr.io/astral-sh/uv:latest /uv /uvx /bin/

# install libmagic
RUN apt-get update && apt-get install -y libmagic-dev

ADD ./python /app
ADD ./expose.pdf /app/expose.pdf


WORKDIR /app
RUN uv sync
RUN mkdir -p /app/output

FROM dunglas/frankenphp:1.9-php8.3-trixie AS frankenphp

COPY --from=ghcr.io/astral-sh/uv:latest /uv /uvx /bin/

# install libmagic
RUN apt-get update && apt-get install -y libmagic-dev

ADD ./python /app
COPY --from=python-image /app/.venv /app/.venv

# copy python binaries and shared libraries
COPY --from=python-image /app/.venv/bin/python /app/.venv/bin/python
COPY --from=python-image /usr/local/lib/python3.12/ /usr/local/lib/python3.12/
COPY --from=python-image /usr/local/lib/libpython3.12.so.1.0 /usr/local/lib/libpython3.12.so.1.0

WORKDIR /app

# Test that the python script works

# RUN mkdir -p /app/output
# ADD ./expose.pdf /app/expose.pdf
# RUN /app/.venv/bin/python prepare-pdf.py /app/output /app/expose.pdf 1234567890
