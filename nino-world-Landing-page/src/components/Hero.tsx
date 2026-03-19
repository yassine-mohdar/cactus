import { motion } from "framer-motion";
import ninoHero from "@/assets/nino-hero.png";

const bounceTransition = {
  type: "spring" as const,
  stiffness: 260,
  damping: 20,
};

const Hero = () => (
  <section className="relative overflow-hidden py-12 md:py-20 px-4">
    <div className="container mx-auto">
      <div className="flex flex-col md:flex-row items-center gap-8 md:gap-4">
        {/* Left: Text content */}
        <div className="flex-1 text-center md:text-left z-10 order-2 md:order-1">
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
          >
            <span className="inline-block bg-sage px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-4">
              🌵 Welcome to NinoWorld
            </span>
            <h1 className="font-display text-5xl md:text-7xl font-bold leading-tight text-foreground mb-4">
              Adopt your new<br />
              <span className="text-sage" style={{ filter: "brightness(0.7) saturate(1.5)" }}>green friend</span>
            </h1>
            <p className="font-body text-lg md:text-xl text-muted-foreground leading-relaxed max-w-md mx-auto md:mx-0 mb-8">
              Every cactus has a name, a story, and a heart full of love. 
              Find the one who's been waiting just for you. 💚
            </p>
            <div className="flex flex-col sm:flex-row gap-3 justify-center md:justify-start">
              <motion.button
                whileHover={{ scale: 1.05, rotate: 1 }}
                whileTap={{ scale: 0.95 }}
                transition={bounceTransition}
                className="bg-primary text-primary-foreground font-body font-bold px-8 py-4 rounded-full text-lg sticker-shadow hover:brightness-95 transition-all"
              >
                🌿 Adopt Nino
              </motion.button>
              <motion.button
                whileHover={{ scale: 1.05, rotate: -1 }}
                whileTap={{ scale: 0.95 }}
                transition={bounceTransition}
                className="bg-secondary text-secondary-foreground font-body font-bold px-8 py-4 rounded-full text-lg sticker-shadow hover:brightness-95 transition-all"
              >
                Meet the Friends ✨
              </motion.button>
            </div>
          </motion.div>
        </div>

        {/* Right: Nino illustration */}
        <div className="flex-1 flex justify-center order-1 md:order-2 relative">
          <motion.div
            animate={{ y: [0, -12, 0] }}
            transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
          >
            <img
              src={ninoHero}
              alt="Nino the cute cactus mascot saying Hi!"
              className="w-64 md:w-96 drop-shadow-2xl"
            />
          </motion.div>
          {/* Speech bubble */}
          <motion.div
            initial={{ opacity: 0, scale: 0.5 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 0.5, ...bounceTransition }}
            className="absolute top-0 right-4 md:right-8 bg-background rounded-3xl px-5 py-3 sticker-shadow"
          >
            <p className="font-display text-2xl font-bold text-foreground">
              I've been waiting<br />for you! 💕
            </p>
          </motion.div>
        </div>
      </div>
    </div>
  </section>
);

export default Hero;
