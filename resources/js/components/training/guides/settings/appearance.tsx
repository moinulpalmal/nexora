import {
    Bullet,
    Bullets,
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
 * Settings → Appearance (`appearance.edit`) — the user's theme.
 * Source: documentation/settings.md §3, ARCHITECTURE.md §9.5.
 */
export default function AppearanceGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Choose how the application looks: light, dark, or one of many colour themes."
                    bn="অ্যাপ্লিকেশন দেখতে কেমন হবে তা বেছে নিন: হালকা, গাঢ়, অথবা অনেকগুলো রঙের থিমের যেকোনো একটি।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en="Everyone who can sign in. No permission is needed."
                    bn="যাঁরা সাইন ইন করতে পারেন, তাঁদের সবাই। কোনো পারমিশন লাগে না।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en={
                            <>
                                In Settings, choose <Ui>Appearance</Ui>.
                            </>
                        }
                        bn={
                            <>
                                Settings-এ <Ui>Appearance</Ui> বেছে নিন।
                            </>
                        }
                    >
                        <Shot
                            id="overview"
                            en="Light, Dark and System at the top, and below them every theme, grouped into Light and Dark and drawn in its own colours."
                            bn="ওপরে Light, Dark ও System, আর তার নিচে সব থিম — Light ও Dark এই দুই ভাগে সাজানো, প্রতিটি নিজের রঙে আঁকা।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>Light</Ui>, <Ui>Dark</Ui> or{' '}
                                <Ui>System</Ui> for the plain look, or click any
                                theme below for colour. The whole application
                                changes at once; there is nothing to save.
                            </>
                        }
                        bn={
                            <>
                                সাধারণ চেহারার জন্য <Ui>Light</Ui>,{' '}
                                <Ui>Dark</Ui> বা <Ui>System</Ui> চাপুন, অথবা
                                রঙের জন্য নিচের যেকোনো থিমে ক্লিক করুন। সাথে
                                সাথে পুরো অ্যাপ্লিকেশন বদলে যায়; সেভ করার কিছু
                                নেই।
                            </>
                        }
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="System"
                        en="Follows your computer: light by day and dark by night, if your computer switches."
                        bn="আপনার কম্পিউটারকে অনুসরণ করে: কম্পিউটার বদলালে দিনে হালকা, রাতে গাঢ়।"
                    />
                </Fields>
                <Bullets>
                    <Bullet
                        en="Your choice is saved to your account, so it follows you to every computer you sign in on."
                        bn="আপনার পছন্দ আপনার অ্যাকাউন্টে সংরক্ষিত হয়, তাই যে কম্পিউটারেই সাইন ইন করুন, এটি আপনার সাথে যায়।"
                    />
                    <Bullet
                        en="The sign-in page on a computer you have used shows your theme too."
                        bn="যে কম্পিউটার আপনি আগে ব্যবহার করেছেন, তার সাইন-ইন পাতাতেও আপনার থিম দেখা যায়।"
                    />
                </Bullets>
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="profile.edit"
                    en="The rest of your account."
                    bn="আপনার অ্যাকাউন্টের বাকি অংশ।"
                />
            </GuideSection>
        </Guide>
    );
}
