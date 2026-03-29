import { createServer } from 'http';
import app from "./app";
import { logger } from "./lib/logger";
import { createSocketServer } from "./socket/socketServer";
import { seedAdmin } from "./lib/seedAdmin.js";
import { PORT, IS_PRODUCTION } from './config.js';

const httpServer = createServer(app);
createSocketServer(httpServer);

seedAdmin().catch((err) => logger.error({ err }, 'Admin seeding failed'));

if (IS_PRODUCTION) {
  console.info('[auth] OTP rate limits are in-memory. Not shared across server restarts/instances.');
}

httpServer.listen(PORT, (err?: Error) => {
  if (err) {
    logger.error({ err }, "Error listening on port");
    process.exit(1);
  }

  logger.info({ port: PORT }, "Server listening with Socket.io");
});
