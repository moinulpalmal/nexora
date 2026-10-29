import {
    Bullet,
    Bullets,
    Callout,
    Field,
    Fields,
    Guide,
    GuideSection,
    Para,
    RelatedGuide,
    Shot,
    Step,
    Steps,
    Ui,
} from '@/components/training/vocabulary';

/**
 * Dashboard (`dashboard`) — and the shell every screen shares: the sidebar,
 * the header's company switcher, help button and bell, and the user menu.
 * The dashboard holds no widgets yet; rewrite this guide when it does.
 * Source: ARCHITECTURE.md §8.3, §9.7, §9.10; documentation/training.md.
 */
export default function DashboardGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Where you land after signing in, and the frame around every screen: the sidebar on the left, and the header with the help button and your notifications. Learn the frame once and every screen is easier."
                    bn="সাইন ইন করার পর আপনি যেখানে পৌঁছান, আর প্রতিটি স্ক্রিনের চারপাশের কাঠামো: বাঁ দিকের সাইডবার, এবং ওপরের হেডার — যেখানে সাহায্যের বোতাম ও আপনার নোটিফিকেশন থাকে। কাঠামোটি একবার চিনে নিলে প্রতিটি স্ক্রিন সহজ হয়ে যায়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en="Everyone who can sign in. What the sidebar lists depends on your permissions."
                    bn="যাঁরা সাইন ইন করতে পারেন, তাঁদের সবাই। সাইডবারে কী কী দেখা যাবে তা আপনার পারমিশনের ওপর নির্ভর করে।"
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en={
                        <>
                            Sign in with your username or your email — either
                            works in the same box. Your account is made by an
                            administrator; there is no way to sign up. If you
                            forget your password, use{' '}
                            <Ui>Forgot your password?</Ui> on the sign-in page.
                        </>
                    }
                    bn={
                        <>
                            আপনার ইউজারনেম অথবা ইমেইল দিয়ে সাইন ইন করুন — একই
                            ঘরে দুটোই চলে। আপনার অ্যাকাউন্ট একজন
                            অ্যাডমিনিস্ট্রেটর তৈরি করে দেন; নিজে সাইন আপ করার
                            কোনো উপায় নেই। পাসওয়ার্ড ভুলে গেলে সাইন-ইন পাতার{' '}
                            <Ui>Forgot your password?</Ui> ব্যবহার করুন।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="After signing in you land on the Dashboard. The sidebar lists only the screens you may open, grouped by module; a group you have nothing in is not shown at all."
                        bn="সাইন ইন করার পর আপনি Dashboard-এ পৌঁছান। সাইডবারে কেবল সেই স্ক্রিনগুলোই থাকে যেগুলো আপনি খুলতে পারেন, মডিউল অনুযায়ী ভাগ করা; যে ভাগে আপনার কিছুই নেই সেটি দেখাই যায় না।"
                    >
                        <Shot
                            id="overview"
                            en="The Dashboard with the sidebar's groups on the left and the header's help button and bell on the right."
                            bn="Dashboard — বাঁ দিকে সাইডবারের ভাগগুলো, ডান দিকে হেডারের সাহায্যের বোতাম ও ঘণ্টা।"
                        />
                    </Step>
                    <Step
                        en="Click a group's name to fold or unfold it. Every group starts folded after you sign in, and the sidebar remembers what you open. The button at the top left of the header shrinks the whole sidebar to icons."
                        bn="কোনো ভাগের নামে ক্লিক করলে সেটি গুটিয়ে যায় বা খুলে যায়। সাইন ইনের পর প্রতিটি ভাগ গোটানো অবস্থায় থাকে, আর আপনি যা খোলেন সাইডবার তা মনে রাখে। হেডারের একদম বাঁ দিকের বোতামটি পুরো সাইডবারকে ছোট করে শুধু আইকনে নিয়ে আসে।"
                    />
                    <Step
                        en={
                            <>
                                On any screen, the <Ui>?</Ui> in the header
                                opens that screen's guide over it. Close it with
                                the ✕. <Ui>Training</Ui> in the sidebar lists
                                every guide, including guides to screens you
                                cannot open yet.
                            </>
                        }
                        bn={
                            <>
                                যেকোনো স্ক্রিনে হেডারের <Ui>?</Ui> সেই স্ক্রিনের
                                গাইডটি স্ক্রিনের ওপরেই খুলে দেয়। ✕ দিয়ে বন্ধ
                                করুন। সাইডবারের <Ui>Training</Ui>-এ সব গাইডের
                                তালিকা আছে — যে স্ক্রিন আপনি এখনো খুলতে পারেন
                                না, তার গাইডও।
                            </>
                        }
                    />
                    <Step
                        en={
                            <>
                                If you work in Projects, Accounts or HRM, the
                                company switcher in the header shows the company
                                you are working in. Every record you see or add
                                there belongs to that company; pick another from
                                the switcher to change it. You only see the
                                companies an administrator has given you — with
                                just one, it is chosen for you.
                            </>
                        }
                        bn={
                            <>
                                Projects, Accounts বা HRM-এ কাজ করলে হেডারের
                                কোম্পানি সুইচারে দেখা যায় আপনি কোন কোম্পানিতে
                                কাজ করছেন। সেখানে যে রেকর্ড দেখেন বা যোগ করেন,
                                সবই সেই কোম্পানির; বদলাতে সুইচার থেকে অন্যটি
                                বেছে নিন। একজন অ্যাডমিনিস্ট্রেটর আপনাকে যে
                                কোম্পানিগুলো দিয়েছেন শুধু সেগুলোই দেখা যায় —
                                একটি থাকলে সেটিই আপনা থেকে বেছে নেওয়া হয়।
                            </>
                        }
                    />
                    <Step
                        en={
                            <>
                                The bell shows how many notifications you have
                                not read. Open it to read them; click one to go
                                where it points. <Ui>Mark all read</Ui> clears
                                the count.
                            </>
                        }
                        bn={
                            <>
                                ঘণ্টা চিহ্নে দেখা যায় কতগুলো নোটিফিকেশন আপনি
                                এখনো পড়েননি। পড়তে এটি খুলুন; কোনোটিতে ক্লিক
                                করলে সেটি যেখানে নির্দেশ করে সেখানে চলে যান।{' '}
                                <Ui>Mark all read</Ui> সংখ্যাটি মুছে দেয়।
                            </>
                        }
                    >
                        <Shot
                            id="bell"
                            en="The bell open, with an unread notification that an administrator changed your roles."
                            bn="ঘণ্টা খোলা অবস্থায় — একটি না-পড়া নোটিফিকেশন: একজন অ্যাডমিনিস্ট্রেটর আপনার রোল বদলেছেন।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Your name at the bottom of the sidebar opens
                                your menu: <Ui>Settings</Ui> for your own
                                profile, password and theme, and{' '}
                                <Ui>Log out</Ui>.
                            </>
                        }
                        bn={
                            <>
                                সাইডবারের নিচে আপনার নামে ক্লিক করলে আপনার মেনু
                                খোলে: নিজের প্রোফাইল, পাসওয়ার্ড ও থিমের জন্য{' '}
                                <Ui>Settings</Ui>, আর <Ui>Log out</Ui>।
                            </>
                        }
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Notifications"
                        en="Sent to you by the application — for example when an administrator changes your roles or sets your password. A new one arrives while you work, with a short message at the corner of the screen."
                        bn="অ্যাপ্লিকেশন আপনাকে পাঠায় — যেমন, কোনো অ্যাডমিনিস্ট্রেটর আপনার রোল বদলালে বা পাসওয়ার্ড দিলে। কাজ করার সময়ই নতুন নোটিফিকেশন আসে, স্ক্রিনের কোণে একটি ছোট বার্তাসহ।"
                    />
                </Fields>
                <Bullets>
                    <Bullet
                        en="Messages after you save something appear at the corner of the screen and go away by themselves after a few seconds. Green means it worked; yellow means it was refused for a reason you can fix; red means it was refused by a rule."
                        bn="কিছু সেভ করার পর বার্তাগুলো স্ক্রিনের কোণে দেখা যায় এবং কয়েক সেকেন্ড পর নিজে থেকেই চলে যায়। সবুজ মানে কাজটি হয়েছে; হলুদ মানে এমন কারণে প্রত্যাখ্যাত হয়েছে যা আপনি ঠিক করতে পারেন; লাল মানে কোনো নিয়মের কারণে প্রত্যাখ্যাত হয়েছে।"
                    />
                    <Bullet
                        en="A dialog never closes when you click beside it or press Escape, so a half-filled form is never lost by accident. Use Cancel or the ✕."
                        bn="ডায়ালগের পাশে ক্লিক করলে বা Escape চাপলে এটি কখনো বন্ধ হয় না, তাই অর্ধেক পূরণ করা ফর্ম ভুল করে হারায় না। Cancel বা ✕ ব্যবহার করুন।"
                    />
                </Bullets>
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="info"
                    en="A screen you expected is missing from the sidebar? You do not hold its permission. Ask an administrator — and read its guide in Training meanwhile."
                    bn="যে স্ক্রিনটি আশা করছিলেন সেটি সাইডবারে নেই? এর পারমিশন আপনার নেই। একজন অ্যাডমিনিস্ট্রেটরকে বলুন — আর ততক্ষণে Training-এ এর গাইডটি পড়ে নিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="profile.edit"
                    en="Your own account."
                    bn="আপনার নিজের অ্যাকাউন্ট।"
                />
                <RelatedGuide
                    to="appearance.edit"
                    en="Light, dark and colour themes."
                    bn="হালকা, গাঢ় ও রঙের থিম।"
                />
            </GuideSection>
        </Guide>
    );
}
