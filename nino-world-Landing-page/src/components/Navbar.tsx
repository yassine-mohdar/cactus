import { motion } from "framer-motion";

const Navbar = () => (
  <motion.nav
    initial={{ y: -20, opacity: 0 }}
    animate={{ y: 0, opacity: 1 }}
    className="sticky top-0 z-50 bg-background/80 backdrop-blur-md py-3 px-4"
  >
    <div className="container mx-auto flex items-center justify-between">
      <a href="#" className="font-display text-2xl font-bold text-foreground">
        🌵 NinoWorld
      </a>
      <div className="hidden md:flex items-center gap-6 font-body text-sm font-semibold text-foreground/70">
        <a href="#friends" className="hover:text-foreground transition-colors">Friends</a>
        <a href="#vibes" className="hover:text-foreground transition-colors">Vibes</a>
        <a href="#how" className="hover:text-foreground transition-colors">How it Works</a>
        <a href="#box" className="hover:text-foreground transition-colors">Adoption Box</a>
      </div>
      <motion.button
        whileHover={{ scale: 1.05 }}
        whileTap={{ scale: 0.95 }}
        className="bg-primary text-primary-foreground font-body font-bold px-5 py-2 rounded-full text-sm sticker-shadow"
      >
        Adopt Now 💚
      </motion.button>
    </div>
  </motion.nav>
);

export default Navbar;
