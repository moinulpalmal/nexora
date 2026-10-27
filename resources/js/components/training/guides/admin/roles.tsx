import { ListBasics } from '@/components/training/guides/shared/list-basics';
import {
    Bullet,
    Bullets,
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
 * Admin → Roles (`admin.roles.index`), with its New role / Edit dialog.
 * Source: documentation/admin.md §3.
 */
export default function RolesGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="A role is a named bundle of permissions. Users get their abilities by holding roles, so this is where you decide what each kind of user may do. The application itself checks permissions, never role names — a role is only a convenient way to hand out many at once."
                    bn="রোল হলো একগুচ্ছ পারমিশনের একটি নাম। ব্যবহারকারীরা রোল পাওয়ার মাধ্যমেই কাজের অনুমতি পান, তাই কোন ধরনের ব্যবহারকারী কী করতে পারবেন তা এখানেই ঠিক করা হয়। অ্যাপ্লিকেশন নিজে সবসময় পারমিশন যাচাই করে, রোলের নাম নয় — রোল শুধু একসাথে অনেকগুলো পারমিশন দেওয়ার একটি সহজ উপায়।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>admin.roles.view</Ui> opens the list;{' '}
                            <Ui>admin.roles.create</Ui>,{' '}
                            <Ui>admin.roles.update</Ui> and{' '}
                            <Ui>admin.roles.delete</Ui> add, edit and remove
                            roles.
                        </>
                    }
                    bn={
                        <>
                            <Ui>admin.roles.view</Ui> থাকলে তালিকা খোলা যায়;{' '}
                            <Ui>admin.roles.create</Ui>,{' '}
                            <Ui>admin.roles.update</Ui> ও{' '}
                            <Ui>admin.roles.delete</Ui> দিয়ে রোল যোগ, সম্পাদনা
                            ও মোছা যায়।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="before">
                <Para
                    en="Three roles come with the application: super-admin (everything), admin (day-to-day administration) and viewer (read-only). Look at what they already grant before making a new one — the Permissions screen shows it."
                    bn="অ্যাপ্লিকেশনের সাথে তিনটি রোল আগে থেকেই আছে: super-admin (সবকিছু), admin (দৈনন্দিন পরিচালনা) ও viewer (শুধু দেখা)। নতুন রোল তৈরির আগে দেখে নিন এগুলো আগেই কী অনুমতি দেয় — Permissions স্ক্রিনে তা দেখা যায়।"
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en="Open Admin → Roles. Each row shows how many permissions the role carries and how many users hold it."
                        bn="Admin → Roles খুলুন। প্রতিটি সারিতে দেখা যায় রোলটিতে কতগুলো পারমিশন আছে এবং কতজন ব্যবহারকারীর এটি আছে।"
                    >
                        <Shot
                            id="overview"
                            en="The roles with their permission and user counts; super-admin is marked as bypassing all checks."
                            bn="রোলগুলো, প্রতিটির পারমিশন ও ব্যবহারকারীর সংখ্যাসহ; super-admin-এর পাশে লেখা থাকে এটি সব যাচাই এড়িয়ে যায়।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Press <Ui>New role</Ui> and give it a name in
                                lowercase with hyphens, such as{' '}
                                <Ui>data-entry</Ui>.
                            </>
                        }
                        bn={
                            <>
                                <Ui>New role</Ui> চাপুন এবং ছোট হাতের অক্ষর ও
                                হাইফেন দিয়ে একটি নাম দিন, যেমন{' '}
                                <Ui>data-entry</Ui>।
                            </>
                        }
                    />
                    <Step
                        en="Tick what the role may do. Permissions are grouped by module; hover one, or a module heading, to read what it allows and where."
                        bn="রোলটি কী কী করতে পারবে তাতে টিক দিন। পারমিশনগুলো মডিউল অনুযায়ী সাজানো; কোনোটির ওপর, বা মডিউলের শিরোনামের ওপর মাউস রাখলে দেখা যায় সেটি কী এবং কোথায় অনুমতি দেয়।"
                    >
                        <Shot
                            id="form"
                            en="New role: the name, and the permission picker grouped by module."
                            bn="New role: নাম, আর মডিউল অনুযায়ী সাজানো পারমিশন বাছাইয়ের তালিকা।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                Save with <Ui>Save &amp; close</Ui>. Then give
                                the role to people on the Users screen.
                            </>
                        }
                        bn={
                            <>
                                <Ui>Save &amp; close</Ui> দিয়ে সেভ করুন। তারপর
                                Users স্ক্রিনে গিয়ে মানুষকে রোলটি দিন।
                            </>
                        }
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Name"
                        en="Lowercase letters, digits and single hyphens. It is a label for people; renaming a role changes nothing about what its holders may do."
                        bn="ছোট হাতের অক্ষর, সংখ্যা ও একটি করে হাইফেন। এটি মানুষের বোঝার জন্য একটি নাম; রোলের নাম বদলালে এর ধারকেরা কী করতে পারবেন তাতে কিছুই বদলায় না।"
                    />
                    <Field
                        label="Permissions"
                        en="Every permission the application knows, grouped by module. The list itself cannot be changed here — permissions are part of the application."
                        bn="অ্যাপ্লিকেশনের জানা সব পারমিশন, মডিউল অনুযায়ী সাজানো। তালিকাটি এখান থেকে বদলানো যায় না — পারমিশনগুলো অ্যাপ্লিকেশনেরই অংশ।"
                    />
                    <Field
                        label="Users"
                        en="How many users hold the role. A role with users cannot be deleted; its delete button is disabled and says why when you point at it."
                        bn="কতজন ব্যবহারকারীর এই রোলটি আছে। যে রোল কারো আছে তা মোছা যায় না; এর মোছার বোতাম বন্ধ থাকে, আর মাউস রাখলে কারণটি দেখায়।"
                    />
                </Fields>
                <Bullets>
                    <Bullet
                        en="super-admin has no edit button and counts its permissions as all: it passes every check whatever is ticked, so there is nothing to change."
                        bn="super-admin-এর কোনো এডিট বোতাম নেই, আর এর পারমিশনের সংখ্যায় লেখা থাকে all: কোনটিতে টিক আছে তা যা-ই হোক, এটি সব যাচাই পার হয়ে যায়, তাই বদলানোর কিছু নেই।"
                    />
                </Bullets>
                <ListBasics />
            </GuideSection>

            <GuideSection kind="messages">
                <Message
                    text="This role is still assigned to users. Remove it from them first."
                    en="Take the role off every user on the Users screen, then delete it."
                    bn="আগে Users স্ক্রিনে প্রত্যেক ব্যবহারকারীর কাছ থেকে রোলটি সরিয়ে নিন, তারপর মুছুন।"
                />
                <Message
                    text="The super-admin role cannot be deleted."
                    en="Without it nobody could administer everything. It cannot be edited either."
                    bn="এটি না থাকলে কেউ সবকিছু পরিচালনা করতে পারতেন না। এটি সম্পাদনাও করা যায় না।"
                />
                <Message
                    text='Use lowercase letters, digits and single hyphens, e.g. "data-entry".'
                    en="The name has a capital letter or a space in it, or a hyphen at the start, at the end or twice in a row."
                    bn="নামে বড় হাতের অক্ষর বা ফাঁকা জায়গা আছে, অথবা শুরুতে, শেষে বা পরপর দুবার হাইফেন আছে।"
                />
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="danger"
                    en="Editing a role changes every user who holds it, from their next click — nothing asks you to confirm, and nobody is told. Check the Users column before ticking a powerful permission on a role many people share."
                    bn="রোল সম্পাদনা করলে যাঁদের এই রোল আছে তাঁদের সবার অনুমতি পরের ক্লিক থেকেই বদলে যায় — নিশ্চিত করতে কিছু জিজ্ঞেস করা হয় না, কাউকে জানানোও হয় না। অনেকের থাকা কোনো রোলে শক্তিশালী পারমিশনে টিক দেওয়ার আগে Users কলামটি দেখে নিন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.permissions"
                    en="Every permission, what it allows and which roles hold it."
                    bn="প্রতিটি পারমিশন, এটি কী অনুমতি দেয় এবং কোন কোন রোলে আছে।"
                />
                <RelatedGuide
                    to="admin.users"
                    en="Giving a role to a user."
                    bn="কোনো ব্যবহারকারীকে রোল দেওয়া।"
                />
            </GuideSection>
        </Guide>
    );
}
