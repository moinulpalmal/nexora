import {
    Callout,
    Field,
    Fields,
    Guide,
    GuideSection,
    Message,
    Para,
    RelatedGuide,
    Shot,
    Step,
    Steps,
    Ui,
} from '@/components/training/vocabulary';

/**
 * Settings → Profile (`profile.edit`) — the signed-in user's own account.
 * Source: documentation/settings.md §1.
 */
export default function ProfileGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Your own account: your photo, name and phone, and the username and email you sign in with."
                    bn="আপনার নিজের অ্যাকাউন্ট: আপনার ছবি, নাম ও ফোন, আর যে ইউজারনেম ও ইমেইল দিয়ে আপনি সাইন ইন করেন।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en="Everyone who can sign in. No permission is needed: your account is yours to keep up to date."
                    bn="যাঁরা সাইন ইন করতে পারেন, তাঁদের সবাই। কোনো পারমিশন লাগে না: নিজের অ্যাকাউন্ট হালনাগাদ রাখা আপনারই কাজ।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en={
                            <>
                                Open your name at the bottom of the sidebar and
                                choose <Ui>Settings</Ui>. Profile opens first.
                            </>
                        }
                        bn={
                            <>
                                সাইডবারের নিচে আপনার নামে ক্লিক করে{' '}
                                <Ui>Settings</Ui> বেছে নিন। প্রথমে Profile খোলে।
                            </>
                        }
                    >
                        <Shot
                            id="overview"
                            en="The profile form: photo, name, phone, username and email, with the employee code below."
                            bn="প্রোফাইল ফর্ম: ছবি, নাম, ফোন, ইউজারনেম ও ইমেইল, নিচে এমপ্লয়ি কোড।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Change what you need and press <Ui>Save</Ui>.
                            </>
                        }
                        bn={
                            <>
                                যা দরকার বদলান, তারপর <Ui>Save</Ui> চাপুন।
                            </>
                        }
                    />
                    <Step
                        en={
                            <>
                                Changing your username or email makes a{' '}
                                <Ui>Current password</Ui> box appear. Type your
                                password there before saving.
                            </>
                        }
                        bn={
                            <>
                                ইউজারনেম বা ইমেইল বদলালে একটি{' '}
                                <Ui>Current password</Ui> ঘর দেখা যায়। সেভ করার
                                আগে সেখানে আপনার পাসওয়ার্ড লিখুন।
                            </>
                        }
                    >
                        <Shot
                            id="sign-in-change"
                            en="A changed username, with the Current password box it brings up."
                            bn="বদলানো ইউজারনেম, আর এর ফলে যে Current password ঘরটি দেখা যায়।"
                        />
                    </Step>
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Photo"
                        en="JPG, PNG or WebP, up to 2 MB. Tick Remove my photo to go back to your initials."
                        bn="JPG, PNG বা WebP, সর্বোচ্চ ২ MB। আবার নামের আদ্যক্ষরে ফিরে যেতে Remove my photo-তে টিক দিন।"
                    />
                    <Field
                        label="Username"
                        en="One of the two ways you sign in. Changing only capital letters to small ones is not a change."
                        bn="সাইন ইনের দুটি উপায়ের একটি। শুধু বড় হাতের অক্ষর ছোট করলে সেটি বদল হিসেবে ধরা হয় না।"
                    />
                    <Field
                        label="Email address"
                        en="The other way you sign in, and where the application's mails reach you."
                        bn="সাইন ইনের অন্য উপায়টি, আর অ্যাপ্লিকেশনের ইমেইলগুলো আপনার কাছে এখানেই পৌঁছায়।"
                    />
                    <Field
                        label="Current password"
                        en="Appears only when the username or email differs from what is saved. It keeps an unattended, signed-in computer from being used to take your account over."
                        bn="কেবল তখনই দেখা যায় যখন ইউজারনেম বা ইমেইল সংরক্ষিতটির চেয়ে আলাদা হয়। কেউ আপনার সাইন-ইন করা কম্পিউটার খোলা পেয়ে যাতে অ্যাকাউন্টটি দখল করতে না পারেন, সেজন্য এটি।"
                    />
                    <Field
                        label="Employee code"
                        en="Shown, not editable. Your employee code and your roles are managed by an administrator."
                        bn="দেখা যায়, কিন্তু বদলানো যায় না। আপনার এমপ্লয়ি কোড ও রোল একজন অ্যাডমিনিস্ট্রেটর দেখাশোনা করেন।"
                    />
                </Fields>
            </GuideSection>

            <GuideSection kind="messages">
                <Message text="Profile updated." en="Saved." bn="সেভ হয়েছে।" />
                <Message
                    text="Enter your current password to change your username or email."
                    en="The Current password box was left empty."
                    bn="Current password ঘরটি ফাঁকা রাখা হয়েছিল।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="warn"
                    en="A new username or email works from your next sign-in — the old one stops working at once. Note the new one before you sign out."
                    bn="নতুন ইউজারনেম বা ইমেইল আপনার পরের সাইন-ইন থেকেই কাজ করে — পুরোনোটি সাথে সাথে বন্ধ হয়ে যায়। সাইন আউট করার আগে নতুনটি মনে রাখুন।"
                />
                <Callout
                    tone="info"
                    en="There is no way to delete your own account. When you leave, an administrator retires it."
                    bn="নিজের অ্যাকাউন্ট নিজে মুছে ফেলার কোনো উপায় নেই। আপনি চলে গেলে একজন অ্যাডমিনিস্ট্রেটর এটি বন্ধ করেন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="security.edit"
                    en="Changing your password."
                    bn="আপনার পাসওয়ার্ড বদলানো।"
                />
                <RelatedGuide
                    to="appearance.edit"
                    en="Choosing how the application looks."
                    bn="অ্যাপ্লিকেশন দেখতে কেমন হবে তা বেছে নেওয়া।"
                />
            </GuideSection>
        </Guide>
    );
}
