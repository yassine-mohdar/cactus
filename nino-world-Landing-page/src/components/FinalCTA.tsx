import { motion } from "framer-motion";
import mimiImg from "@/assets/mimi-character.png";

const FinalCTA = () => (
  <section className="py-16 md:py-24 px-4 bg-blush/40">
    <div className="container mx-auto text-center">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
      >
        <motion.img
          src={mimiImg}
          alt="Mimi waiting for adoption"
          className="w-40 md:w-56 mx-auto mb-6 drop-shadow-xl"
          animate={{ y: [0, -10, 0] }}
          transition={{ duration: 3.5, repeat: Infinity, ease: "easeInOut" }}
        />
        <h2 className="font-display text-4xl md:text-6xl font-bold text-foreground mb-4">
          Someone's waiting for you...
        </h2>
        <p className="font-body text-lg text-muted-foreground max-w-md mx-auto mb-8 leading-relaxed">
          Every cactus in NinoWorld is looking for a warm home and a loving human. 
          Will you be the one? 💕
        </p>
        <motion.button
          whileHover={{ scale: 1.05, rotate: 1 }}
          whileTap={{ scale: 0.95 }}
          className="bg-primary text-primary-foreground font-body font-bold px-10 py-4 rounded-full text-lg sticker-shadow-lg"
        >
          🌵 Start Your Adoption
        </motion.button>
      </motion.div>
    </div>
  </section>
);

export default FinalCTA;
