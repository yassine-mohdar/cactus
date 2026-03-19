import { motion } from "framer-motion";
import ninoImg from "@/assets/nino-hero.png";

const Footer = () => (
  <footer className="py-12 px-4 bg-sage/30 relative overflow-hidden">
    <div className="container mx-auto text-center relative z-10">
      <motion.img
        src={ninoImg}
        alt="Nino waving goodbye"
        className="w-24 mx-auto mb-4 opacity-80"
        animate={{ rotate: [0, 5, -5, 0] }}
        transition={{ duration: 3, repeat: Infinity, ease: "easeInOut" }}
      />
      <p className="font-display text-2xl font-bold text-foreground mb-2">
        See you soon, friend! 👋
      </p>
      <p className="font-body text-sm text-muted-foreground mb-6">
        NinoWorld — Where every cactus finds a home 🌵
      </p>
      <div className="flex justify-center gap-6 font-body text-xs text-muted-foreground">
        <a href="#" className="hover:text-foreground transition-colors">Terms</a>
        <a href="#" className="hover:text-foreground transition-colors">Privacy</a>
        <a href="#" className="hover:text-foreground transition-colors">Contact</a>
        <a href="#" className="hover:text-foreground transition-colors">Instagram</a>
      </div>
      <p className="font-body text-xs text-muted-foreground/50 mt-4">
        © 2026 NinoWorld. Made with 💚 in Morocco.
      </p>
    </div>
  </footer>
);

export default Footer;
