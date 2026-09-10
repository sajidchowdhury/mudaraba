import { Head } from "@inertiajs/react";
import { Button, Badge } from "@/Components/ui";
import {
    Layers, TrendingUp, Users, ShieldCheck, Calculator,
    FileSpreadsheet, ArrowRight, Sparkles, Moon, Wallet,
    PieChart, Settings2, Zap, Code2, ExternalLink,
    MessageCircle, Facebook,
} from "lucide-react";

interface HomeProps {
    appName: string;
}

export default function Home({ appName }: HomeProps) {
    const features = [
        {
            icon: Calculator,
            title: "৮-ফেজ ক্যালকুলেশন ইঞ্জিন",
            desc: "এক্সেলের মতো নির্ভুল প্রফিট ক্যালকুলেশন — স্বয়ংক্রিয়ভাবে। বিনিয়োগকারী, সেক্টর, টায়ার, রিটেইনড আর্নিংস — সব এক ইঞ্জিনে।",
        },
        {
            icon: Wallet,
            title: "বিনিয়োগকারী ব্যবস্থাপনা",
            desc: "বিনিয়োগ যোগ ও ফেরত, ব্যালেন্স ট্র্যাকিং, টায়ার-ভিত্তিক প্রফিট শেয়ারিং — সবকিছু গোছানো।",
        },
        {
            icon: PieChart,
            title: "সেক্টর ওয়াইজ ইনভেস্টমেন্ট",
            desc: "প্রতিটি সেক্টরে আলাদাভাবে ক্যাপিটাল বরাদ্দ করুন, প্রফিট ট্র্যাক করুন, এবং লেজার রাখুন।",
        },
        {
            icon: Zap,
            title: "অটো ক্যালকুলেশন",
            desc: "এক্সেল টেমপ্লেট আপলোড করে এক ক্লিকে মাসের সব হিসাব সম্পন্ন করুন।",
        },
        {
            icon: FileSpreadsheet,
            title: "এক্সেল এক্সপোর্ট",
            desc: "'For Sajid' শিটের হুবহু রূপ — এক ক্লিকে ডাউনলোড, শেয়ার, আর্কাইভ।",
        },
        {
            icon: ShieldCheck,
            title: "অডিট-গ্রেড সিকিউরিটি",
            desc: "প্রতিটি লেনদেন ট্র্যাকেবল, প্রতিটি পরিবর্তন অডিট লগড। সম্পূর্ণ স্বচ্ছতা।",
        },
    ];

    return (
        <>
            <Head title={`${appName} — মুদারাবা প্রফিট ম্যানেজমেন্ট সিস্টেম`} />

            <div className="min-h-screen bg-white">
                {/* ===== Navbar ===== */}
                <header className="sticky top-0 z-50 border-b border-gray-100 bg-white/80 backdrop-blur-sm">
                    <div className="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="size-9 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-md shadow-emerald-200">
                                <Layers className="size-5 text-white" />
                            </div>
                            <div>
                                <p className="font-display text-lg font-semibold leading-none">
                                    {appName}
                                </p>
                                <p className="text-xs text-gray-400">Profit Management System</p>
                            </div>
                        </div>
                        <a href="/login">
                            <Button size="sm" className="bg-emerald-600 hover:bg-emerald-700">
                                <span style={{ fontFamily: 'inherit' }}>Login</span>
                                <ArrowRight className="size-4" />
                            </Button>
                        </a>
                    </div>
                </header>

                {/* ===== Hero Section ===== */}
                <section className="relative overflow-hidden py-20 md:py-32 px-6">
                    {/* Subtle gradient */}
                    <div className="absolute inset-0 bg-gradient-to-b from-emerald-50/30 via-white to-white" />

                    {/* Geometric pattern */}
                    <div className="absolute right-0 top-0 w-1/3 h-full opacity-[0.04] pointer-events-none">
                        <svg viewBox="0 0 300 500" fill="none" className="w-full h-full">
                            <pattern id="heroGrid" width="30" height="30" patternUnits="userSpaceOnUse">
                                <path d="M 30 0 L 0 0 0 30" fill="none" stroke="#10B981" strokeWidth="0.5" />
                            </pattern>
                            <rect width="100%" height="100%" fill="url(#heroGrid)" />
                            <circle cx="200" cy="250" r="100" stroke="#10B981" strokeWidth="0.5" fill="none" />
                            <circle cx="200" cy="250" r="60" stroke="#10B981" strokeWidth="0.5" fill="none" />
                        </svg>
                    </div>

                    <div className="relative z-10 max-w-4xl mx-auto text-center">
                        <Badge variant="primary" className="mb-6">
                            <Sparkles className="size-3" />
                            <span>Islamic Finance Platform</span>
                        </Badge>

                        <h1 className="font-display text-4xl md:text-6xl font-bold tracking-tight leading-tight text-gray-900">
                            সুশৃঙ্খল ব্যবসা,{" "}
                            <span className="text-emerald-600">স্বচ্ছ লেনদেন</span>,
                            <br />
                            শান্তিময় জীবন।
                        </h1>

                        <p className="mt-6 text-lg text-gray-500 max-w-2xl mx-auto leading-relaxed">
                            মুদারাবা প্রফিট-শেয়ারিং সিস্টেম — বিনিয়োগকারী ব্যবস্থাপনা,
                            সেক্টর ইনভেস্টমেন্ট, ৮-ফেজ ক্যালকুলেশন ইঞ্জিন, রিটেইনড আর্নিংস
                            — সবকিছু এক জায়গায়, স্বয়ংক্রিয়।
                        </p>

                        <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="/login">
                                <Button size="lg" className="bg-emerald-600 hover:bg-emerald-700">
                                    লগইন করুন
                                    <ArrowRight className="size-4" />
                                </Button>
                            </a>
                        </div>
                    </div>
                </section>

                {/* ===== Features Section ===== */}
                <section className="py-20 px-6 bg-gray-50/50">
                    <div className="max-w-6xl mx-auto">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                কেন {appName}?
                            </h2>
                            <div className="w-16 h-1 bg-emerald-500 rounded-full mx-auto mb-4" />
                            <p className="text-gray-500 max-w-xl mx-auto">
                                ব্যবসাকে সিস্টেমে রূপ দিন — ঝঞ্ঝাট শেষ, শান্তি শুরু।
                            </p>
                        </div>

                        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            {features.map((feat, i) => {
                                const Icon = feat.icon;
                                return (
                                    <div
                                        key={i}
                                        className="group p-6 rounded-2xl bg-white border border-gray-100 hover:border-emerald-200 hover:shadow-lg transition-all duration-300"
                                    >
                                        <div className="size-12 rounded-xl bg-emerald-50 flex items-center justify-center mb-4 group-hover:bg-emerald-100 transition-colors">
                                            <Icon className="size-6 text-emerald-600" />
                                        </div>
                                        <h3 className="text-lg font-bold text-gray-900 mb-2">
                                            {feat.title}
                                        </h3>
                                        <p className="text-sm text-gray-500 leading-relaxed">
                                            {feat.desc}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* ===== Islamic Finance Principles ===== */}
                <section className="py-20 px-6 bg-white">
                    <div className="max-w-4xl mx-auto text-center">
                        <div className="inline-flex items-center justify-center mb-8">
                            <div className="size-16 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                                <Moon className="size-8 text-emerald-600" />
                            </div>
                        </div>

                        <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6">
                            সৎ ব্যবসা ইবাদতের অংশ
                        </h2>

                        <p className="text-lg text-gray-600 leading-relaxed mb-6">
                            আমরা বিশ্বাস করি — একজন ভালো ব্যবসায়ী সে-ই, যে তার ব্যবসাকে গোছানো রাখে।
                            হিসাব ঠিক রাখে, লেনদেন স্বচ্ছ রাখে, এবং দিন শেষে আল্লাহর সামনে দাঁড়াতে পারে
                            বিনয়ের সাথে। {appName} সেই গোছানো ব্যবসার স্বপ্ন বাস্তবে রূপ দেয়।
                        </p>

                        <div className="inline-block px-8 py-5 rounded-2xl bg-emerald-50 border border-emerald-100 mt-4">
                            <p className="text-2xl text-emerald-700 mb-2" dir="rtl">
                                رَبِّ اشْرَحْ لِي صَدْرِي وَيَسِّرْ لِي أَمْرِي
                            </p>
                            <p className="text-sm text-gray-500 mb-1">
                                হে আমার রব, আমার বক্ষকে প্রশস্ত করুন এবং আমার কাজ সহজ করে দিন।
                            </p>
                            <p className="text-xs text-gray-400">(সূরা ত্বোয়া-হা: ২৫-২৬)</p>
                        </div>
                    </div>
                </section>

                {/* ===== CTA Section ===== */}
                <section className="py-20 px-6 bg-gradient-to-b from-white to-emerald-50/30">
                    <div className="max-w-3xl mx-auto text-center">
                        <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                            আপনার ব্যবসা সিস্টেমে আনতে প্রস্তুত?
                        </h2>
                        <p className="text-gray-500 mb-8">
                            লগইন করে শুরু করুন আপনার মুদারাবা প্রফিট ম্যানেজমেন্ট যাত্রা।
                        </p>
                        <a href="/login">
                            <Button size="lg" className="bg-emerald-600 hover:bg-emerald-700">
                                লগইন করুন
                                <ArrowRight className="size-4" />
                            </Button>
                        </a>
                    </div>
                </section>

                {/* ===== Footer ===== */}
                <footer className="mt-auto border-t border-gray-100 bg-white">
                    <div className="h-1 bg-gradient-to-r from-emerald-400 via-emerald-500 to-emerald-400" />
                    <div className="max-w-6xl mx-auto px-6 py-12">
                        <div className="grid sm:grid-cols-4 gap-8">
                            {/* Brand */}
                            <div>
                                <div className="flex items-center gap-2 mb-4">
                                    <Layers className="size-5 text-emerald-600" />
                                    <h4 className="text-sm font-semibold text-gray-900">{appName}</h4>
                                </div>
                                <p className="text-sm text-gray-500">
                                    ইসলামিক মুদারাবা প্রফিট-শেয়ারিং সিস্টেম
                                </p>
                            </div>

                            {/* Quick links */}
                            <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-4">সিস্টেম</h4>
                                <ul className="space-y-2">
                                    <li><a href="/login" className="text-sm text-gray-500 hover:text-emerald-600 transition-colors">Login</a></li>
                                </ul>
                            </div>

                            {/* Contact */}
                            <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-4">যোগাযোগ</h4>
                                <ul className="space-y-3">
                                    <li>
                                        <a href="https://wa.me/8801787492561" target="_blank" rel="noopener noreferrer" className="text-sm text-gray-500 hover:text-emerald-600 transition-colors inline-flex items-center gap-1.5">
                                            <MessageCircle className="size-3.5" />
                                            WhatsApp: 01787492561
                                        </a>
                                    </li>
                                    <li>
                                        <a href="https://www.facebook.com/mycreativecode" target="_blank" rel="noopener noreferrer" className="text-sm text-gray-500 hover:text-emerald-600 transition-colors inline-flex items-center gap-1.5">
                                            <Facebook className="size-3.5 text-blue-600" />
                                            Facebook
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            {/* Developed by */}
                            <div>
                                <h4 className="text-sm font-semibold text-gray-900 mb-4">ডেভেলপড বাই</h4>
                                <a href="https://mycreativecode.com" target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 group">
                                    <Code2 className="size-4 text-emerald-600 group-hover:scale-110 transition-transform" />
                                    <span className="text-sm font-medium text-gray-700 group-hover:text-emerald-600 transition-colors">
                                        My Creative Code
                                    </span>
                                    <ExternalLink className="size-3 text-gray-300 group-hover:text-emerald-500 transition-colors" />
                                </a>
                            </div>
                        </div>

                        {/* Copyright + Bismillah */}
                        <div className="mt-12 pt-8 border-t border-gray-50 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p className="text-xs text-gray-400">
                                © 2026 {appName} · সব অধিকার সংরক্ষিত
                            </p>
                            <p className="text-base text-emerald-600" dir="rtl">
                                بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
