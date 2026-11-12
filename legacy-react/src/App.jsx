import React from 'react';
import { MotionConfig } from 'motion/react';
import { AppProvider, useApp } from './context/AppContext';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import CarMarketplace from './components/CarMarketplace';
import SellCar from './components/SellCar';
import CarInventory from './components/CarInventory';
import PosTerminal from './components/PosTerminal';
import Dashboard from './components/Dashboard';
import ProductManagement from './components/ProductManagement';
import SupplierManagement from './components/SupplierManagement';
import SalesReports from './components/SalesReports';
import PaymentModal from './components/PaymentModal';
import ReceiptModal from './components/ReceiptModal';
import SettingsModal from './components/SettingsModal';
import CarDetailModal from './components/CarDetailModal';
import CarCheckoutModal from './components/CarCheckoutModal';
import Toaster from './components/Toaster';

function MainLayout() {
  const { activeTab } = useApp();

  return (
    <div className="min-h-screen bg-ink text-neutral-100 flex flex-col">
      <Navbar />

      <main className="flex-1">
        {activeTab === 'buy-car' && <CarMarketplace />}
        {activeTab === 'sell-car' && <SellCar />}
        {activeTab === 'dashboard' && <Dashboard />}
        {activeTab === 'cars' && <CarInventory />}
        {activeTab === 'pos' && <PosTerminal />}
        {activeTab === 'products' && <ProductManagement />}
        {activeTab === 'suppliers' && <SupplierManagement />}
        {activeTab === 'reports' && <SalesReports />}
      </main>

      {/* Global Modals */}
      <CarDetailModal />
      <CarCheckoutModal />
      <PaymentModal />
      <ReceiptModal />
      <SettingsModal />

      <Footer />
    </div>
  );
}

export default function App() {
  return (
    // reducedMotion="user": animasi transform/layout otomatis dimatikan bila
    // pengguna menyalakan "kurangi gerakan" di pengaturan sistem operasinya
    <MotionConfig reducedMotion="user">
      <AppProvider>
        <MainLayout />
      </AppProvider>
      {/* Notifikasi toast (pengganti alert) — dirender sekali, di atas semua modal */}
      <Toaster />
    </MotionConfig>
  );
}
