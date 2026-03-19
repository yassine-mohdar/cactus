import FloatingDoodles from "@/components/FloatingDoodles";
import Navbar from "@/components/Navbar";
import Hero from "@/components/Hero";
import MeetFriends from "@/components/MeetFriends";
import ChooseVibe from "@/components/ChooseVibe";
import HowItWorks from "@/components/HowItWorks";
import AdoptionBoxSection from "@/components/AdoptionBoxSection";
import AppTeaser from "@/components/AppTeaser";
import Community from "@/components/Community";
import ShippingTrust from "@/components/ShippingTrust";
import FAQ from "@/components/FAQ";
import FinalCTA from "@/components/FinalCTA";
import Footer from "@/components/Footer";

const Index = () => (
  <div className="min-h-screen bg-background overflow-hidden">
    <FloatingDoodles />
    <Navbar />
    <Hero />
    <div id="friends"><MeetFriends /></div>
    <div id="vibes"><ChooseVibe /></div>
    <div id="how"><HowItWorks /></div>
    <div id="box"><AdoptionBoxSection /></div>
    <AppTeaser />
    <Community />
    <ShippingTrust />
    <FAQ />
    <FinalCTA />
    <Footer />
  </div>
);

export default Index;
