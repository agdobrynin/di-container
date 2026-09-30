ARG NODE_IMAGE
ARG APP_DIR
ARG VITE_PORT
ARG VITE_PREVIEW_PORT

FROM ${NODE_IMAGE:-node:22-alpine}

RUN mkdir ${APP_DIR:-/var/app} && \
  chown -R 1000:1000 ${APP_DIR:-/var/app} && \
  npm install -g npm@12.1.0

WORKDIR ${APP_DIR:-/var/app}

# Switch to non-root user
USER node

# Expose Vite dev server port
EXPOSE ${VITE_PORT:-5173}
EXPOSE ${VITE_PREVIEW_PORT:-4173}
