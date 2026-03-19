import { motion } from "framer-motion";
import truckImg from "@/assets/nino-truck.png";

const features = [
  { emoji: "🚚", title: "Free Delivery", desc: "Across all of Morocco" },
  { emoji: "💵", title: "Cash on Delivery", desc: "Pay when Nino arrives" },
  { emoji: "💳", title: "Card Payment", desc: "Secure online payment" },
  { emoji: "💬", title: "WhatsApp Support", desc: "Chat with us anytime" },
  { emoji: "📦", title: "Safe Packaging", desc: "Cushioned with love" },
  { emoji: "🔄", title: "Happy Guarantee", desc: "7-day peace of mind" },
];

const ShippingTrust = () => (
  <section className="py-16 md:py-24 px-4 bg-card">
    <div className="container mx-auto">
      <div className="flex flex-col md:flex-row items-center gap-10 md:gap-16">
        <motion.div
          initial={{ x: -100, opacity: 0 }}
          whileInView={{ x: 0, opacity: 1 }}
          viewport={{ once: true }}
          transition={{ type: "spring", stiffness: 80, damping: 20 }}
          className="flex-1 flex justify-center"
        >
          <img
            src={truckImg}
            alt="Nino Express delivery truck"
            className="w-64 md:w-80 drop-shadow-xl"
          />
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="flex-1"
        >
          <span className="inline-block bg-sage px-4 py-1.5 rounded-full font-body font-bold text-xs uppercase tracking-widest text-foreground mb-3">
            🚚 Nino Express
          </span>
          <h2 className="font-display text-4xl md:text-5xl font-bold text-foreground mb-4">
            Safe Travels to You
          </h2>
          <p className="font-body text-lg text-muted-foreground leading-relaxed mb-8">
            Your new friend travels in a cozy cushioned box across Morocco. 
            They arrive happy and ready to say hello! 🌵
          </p>

          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {features.map((f, i) => (
              <motion.div
                key={f.title}
                initial={{ opacity: 0, y: 15 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: i * 0.08 }}
                className="bg-background rounded-2xl p-4 sticker-shadow text-center"
              >
                <span className="text-2xl block mb-2">{f.emoji}</span>
                <p className="font-body text-sm font-bold text-foreground">{f.title}</p>
                <p className="font-body text-xs text-muted-foreground">{f.desc}</p>
              </motion.div>
            ))}
          </div>
        </motion.div>
      </div>
    </div>
  </section>
);

export default ShippingTrust;
