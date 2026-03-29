import { Router, type IRouter } from "express";
import healthRouter from "./health";
import profilesRouter from "./profiles";
import leaderboardRouter from "./leaderboard";
import followsRouter from "./follows";
import tokensRouter from "./tokens";
import matchesRouter from "./matches";
import challengesRouter from "./challenges";
import authRouter from "./auth";
import adminRouter from "./admin";

const router: IRouter = Router();

router.use(authRouter);
router.use(healthRouter);
router.use(profilesRouter);
router.use(leaderboardRouter);
router.use(followsRouter);
router.use(tokensRouter);
router.use(matchesRouter);
router.use(challengesRouter);
router.use(adminRouter);

export default router;
